<?php
/**
 * Handles AI crawler IP range retrieval, caching, and matching.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * IP Registry class.
 */
class MBL_IP_Registry {

	/**
	 * Get the IP ranges for a specific bot from cache or fallback JSON.
	 *
	 * @param string $bot_name The bot slug.
	 * @return array List of IP CIDR ranges.
	 */
	public static function get_ranges( $bot_name ) {
		$cached_ranges = get_transient( 'mbl_ip_ranges_' . $bot_name );
		if ( is_array( $cached_ranges ) ) {
			return $cached_ranges;
		}

		$fallback_data = self::get_fallback_data( $bot_name );
		if ( isset( $fallback_data['ranges'] ) && is_array( $fallback_data['ranges'] ) ) {
			return $fallback_data['ranges'];
		}

		return array();
	}

	/**
	 * Verify if an IP address is authentic for a given bot.
	 *
	 * @param string $ip The visitor IP address.
	 * @param string $bot_name The bot slug.
	 * @return string Verification status: 'verified', 'spoofed', or 'unverified'.
	 */
	public static function verify_ip( $ip, $bot_name ) {
		$fallback_data = self::get_fallback_data( $bot_name );
		if ( empty( $fallback_data ) ) {
			return 'unverified';
		}

		$method = isset( $fallback_data['method'] ) ? $fallback_data['method'] : 'none';

		if ( 'none' === $method ) {
			return 'unverified';
		}

		if ( 'json' === $method ) {
			$ranges = self::get_ranges( $bot_name );
			if ( empty( $ranges ) ) {
				return 'unverified';
			}

			$is_ipv6 = ( strpos( $ip, ':' ) !== false );
			foreach ( $ranges as $range ) {
				if ( $is_ipv6 ) {
					if ( strpos( $range, ':' ) !== false ) {
						if ( self::ip_in_range_ipv6( $ip, $range ) ) {
							return 'verified';
						}
					}
				} else {
					if ( strpos( $range, '.' ) !== false ) {
						if ( self::ip_in_cidr( $ip, $range ) ) {
							return 'verified';
						}
					}
				}
			}
			return 'spoofed';
		}

		if ( 'reverse_dns' === $method ) {
			$domains = isset( $fallback_data['domains'] ) ? $fallback_data['domains'] : array();
			if ( empty( $domains ) ) {
				return 'unverified';
			}

			$hostname = self::resolve_ip_dns_with_timeout( $ip, 2 );
			if ( ! $hostname || $hostname === $ip ) {
				return 'unverified';
			}

			$matched = false;
			foreach ( $domains as $domain ) {
				if ( substr( $hostname, -strlen( $domain ) ) === $domain ) {
					$matched = true;
					break;
				}
			}

			if ( ! $matched ) {
				return 'spoofed';
			}

			// Forward confirm DNS resolution
			$resolved_ip = @gethostbyname( $hostname );
			if ( $resolved_ip === $ip ) {
				return 'verified';
			}

			return 'spoofed';
		}

		return 'unverified';
	}

	/**
	 * Verify IPv4 IP address in CIDR range.
	 *
	 * @param string $ip Visitor IP.
	 * @param string $cidr CIDR block.
	 * @return bool True if IP is in range.
	 */
	public static function ip_in_cidr( $ip, $cidr ) {
		if ( strpos( $cidr, '/' ) === false ) {
			$cidr .= '/32';
		}
		list( $subnet, $bits ) = explode( '/', $cidr );

		$ip_long = ip2long( $ip );
		$subnet_long = ip2long( $subnet );

		if ( false === $ip_long || false === $subnet_long ) {
			return false;
		}

		$mask = -1 << ( 32 - $bits );
		$subnet_long &= $mask;

		return ( $ip_long & $mask ) === $subnet_long;
	}

	/**
	 * Retrieve bot information from local fallback JSON file.
	 *
	 * @param string $bot_name Bot name.
	 * @return array Fallback configuration array.
	 */
	public static function get_fallback_data( $bot_name ) {
		$file_path = MBL_PLUGIN_DIR . 'data/ip-ranges-fallback.json';
		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$json_content = file_get_contents( $file_path );
		$data = json_decode( $json_content, true );

		if ( is_array( $data ) && isset( $data[ $bot_name ] ) ) {
			return $data[ $bot_name ];
		}

		return array();
	}

	/**
	 * Fetch fresh JSON ranges from URLs and save in transients.
	 */
	public static function refresh_ranges() {
		$file_path = MBL_PLUGIN_DIR . 'data/ip-ranges-fallback.json';
		if ( ! file_exists( $file_path ) ) {
			return;
		}

		$json_content = file_get_contents( $file_path );
		$data = json_decode( $json_content, true );
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $data as $bot_name => $bot_config ) {
			if ( isset( $bot_config['method'] ) && 'json' === $bot_config['method'] && ! empty( $bot_config['source'] ) ) {
				$response = wp_remote_get( $bot_config['source'], array( 'timeout' => 10 ) );
				if ( is_wp_error( $response ) ) {
					continue;
				}

				$body = wp_remote_retrieve_body( $response );
				$json_data = json_decode( $body, true );

				if ( ! is_array( $json_data ) || ! isset( $json_data['prefixes'] ) || ! is_array( $json_data['prefixes'] ) ) {
					continue;
				}

				$ranges = array();
				foreach ( $json_data['prefixes'] as $prefix_obj ) {
					if ( isset( $prefix_obj['ipv4Prefix'] ) ) {
						$ranges[] = $prefix_obj['ipv4Prefix'];
					} elseif ( isset( $prefix_obj['ipv6Prefix'] ) ) {
						$ranges[] = $prefix_obj['ipv6Prefix'];
					}
				}

				if ( ! empty( $ranges ) ) {
					set_transient( 'mbl_ip_ranges_' . $bot_name, $ranges, 7 * DAY_IN_SECONDS );
				}
			}
		}
	}

	/**
	 * Resolve PTR record with a timeout by fallback lookup.
	 *
	 * @param string $ip Visitor IP.
	 * @param int    $timeout Timeout in seconds.
	 * @return string Hostname or IP on failure.
	 */
	private static function resolve_ip_dns_with_timeout( $ip, $timeout = 2 ) {
		if ( strpos( $ip, ':' ) !== false ) {
			$hex = unpack( 'H*', inet_pton( $ip ) );
			$hex = reset( $hex );
			$reversed_ip = implode( '.', array_reverse( str_split( $hex ) ) ) . '.ip6.arpa';
			$result = @dns_get_record( $reversed_ip, DNS_PTR );
			if ( ! empty( $result ) && isset( $result[0]['target'] ) ) {
				return $result[0]['target'];
			}
		} else {
			$reversed_ip = implode( '.', array_reverse( explode( '.', $ip ) ) ) . '.in-addr.arpa';
			$result = @dns_get_record( $reversed_ip, DNS_PTR );
			if ( ! empty( $result ) && isset( $result[0]['target'] ) ) {
				return $result[0]['target'];
			}
		}

		return @gethostbyaddr( $ip );
	}

	/**
	 * Verify IPv6 IP address in CIDR range.
	 *
	 * @param string $ip Visitor IP.
	 * @param string $range CIDR block.
	 * @return bool True if IP is in range.
	 */
	private static function ip_in_range_ipv6( $ip, $range ) {
		if ( strpos( $range, '/' ) === false ) {
			$range .= '/128';
		}
		list( $subnet, $bits ) = explode( '/', $range );

		$ip_packed = inet_pton( $ip );
		$subnet_packed = inet_pton( $subnet );

		if ( ! $ip_packed || ! $subnet_packed ) {
			return false;
		}

		$bytes = (int) ceil( $bits / 8 );
		for ( $i = 0; $i < $bytes; $i++ ) {
			$mask = 0xFF;
			if ( $i === $bytes - 1 ) {
				$rem = $bits % 8;
				if ( 0 !== $rem ) {
					$mask = ( 0xFF << ( 8 - $rem ) ) & 0xFF;
				}
			}
			if ( ( ord( $ip_packed[ $i ] ) & $mask ) !== ( ord( $subnet_packed[ $i ] ) & $mask ) ) {
				return false;
			}
		}
		return true;
	}
}
