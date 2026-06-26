<?php
/**
 * Bridges compatibility with popular SEO plugins (Yoast, Rank Math, AIOSEO).
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Bridge class.
 */
class MBL_SEO_Bridge {

	/**
	 * Get the meta description for a post, checking active SEO plugins.
	 *
	 * @param int $post_id The post ID.
	 * @return string The meta description or fallback excerpt.
	 */
	public static function get_post_description( $post_id ) {
		// 1. Check Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
			if ( ! empty( $yoast_desc ) ) {
				return $yoast_desc;
			}
		}

		// 2. Check Rank Math
		if ( class_exists( 'RankMath' ) ) {
			$rm_desc = get_post_meta( $post_id, '_rank_math_description', true );
			if ( ! empty( $rm_desc ) ) {
				return $rm_desc;
			}
		}

		// 3. Check AIOSEO v4+ (Modern)
		if ( class_exists( 'AIOSEO\Plugin\AioSeo' ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'aioseo_posts';
			// Verify if the table actually exists to prevent database errors
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$aioseo_desc = $wpdb->get_var( $wpdb->prepare(
					"SELECT description FROM {$table_name} WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$post_id
				) );
				if ( ! empty( $aioseo_desc ) ) {
					return $aioseo_desc;
				}
			}
		}

		// 4. Check AIOSEO v3 (Legacy)
		$legacy_aioseo_desc = get_post_meta( $post_id, '_aioseop_description', true );
		if ( ! empty( $legacy_aioseo_desc ) ) {
			return $legacy_aioseo_desc;
		}

		// 5. Fallback to post excerpt or trimmed content
		return wp_trim_words( get_the_excerpt( $post_id ), 30 );
	}

	/**
	 * Backwards compatible alias for get_post_description.
	 *
	 * @param int $post_id Post ID.
	 * @return string Description.
	 */
	public static function get_description( $post_id ) {
		return self::get_post_description( $post_id );
	}

	/**
	 * Check if a post is marked as noindex by any active SEO plugin.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if noindex is active, false otherwise.
	 */
	public static function is_post_noindex( $post_id ) {
		// 1. Check Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
			if ( '1' === $yoast_noindex || 1 === (int) $yoast_noindex ) {
				return true;
			}
		}

		// 2. Check Rank Math
		if ( class_exists( 'RankMath' ) ) {
			$rm_robots = get_post_meta( $post_id, '_rank_math_robots', true );
			if ( is_array( $rm_robots ) && in_array( 'noindex', $rm_robots, true ) ) {
				return true;
			}
		}

		// 3. Check AIOSEO v4+ (Modern)
		if ( class_exists( 'AIOSEO\Plugin\AioSeo' ) ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'aioseo_posts';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$aioseo_noindex = $wpdb->get_var( $wpdb->prepare(
					"SELECT robots_noindex FROM {$table_name} WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$post_id
				) );
				if ( '1' === $aioseo_noindex || 1 === (int) $aioseo_noindex ) {
					return true;
				}
			}
		}

		// 4. Check standard WordPress site setting (Reading: Discourage search engines)
		if ( '0' === get_option( 'blog_public' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Backwards compatible alias for is_post_noindex.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if noindex.
	 */
	public static function is_noindex( $post_id ) {
		return self::is_post_noindex( $post_id );
	}
}
