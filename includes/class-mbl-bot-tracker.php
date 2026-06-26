<?php
/**
 * Intercepts requests to track and verify crawler bots.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bot Tracker class.
 */
class MBL_Bot_Tracker {

	/**
	 * Known bot list mapping User-Agent substrings to bot slug names.
	 *
	 * @var array
	 */
	private static $bot_map = array(
		'GPTBot'             => 'gptbot',
		'OAI-SearchBot'      => 'oai-searchbot',
		'ChatGPT-User'       => 'chatgpt-user',
		'ClaudeBot'          => 'claudebot',
		'Google-Extended'    => 'google-extended',
		'Googlebot'          => 'googlebot',
		'PerplexityBot'      => 'perplexitybot',
		'Applebot'           => 'applebot',
		'CCBot'              => 'ccbot',
		'Meta-ExternalAgent' => 'meta-externalagent',
	);

	/**
	 * Initialize the tracker hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'intercept_request' ), 1 );
		add_action( 'mbl_daily_cleanup', array( $this, 'daily_cleanup' ) );
	}

	/**
	 * Parse requests and log bot crawls.
	 */
	public function intercept_request() {
		// 1. Exclude admin requests
		if ( is_admin() ) {
			return;
		}

		// Exclude cron, AJAX, and XML-RPC using defined constants or hooks
		if ( wp_doing_ajax() || wp_doing_cron() || defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}

		// Also check request URI for admin, cron, and xmlrpc requests
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( false !== strpos( $request_uri, 'wp-admin' ) || false !== strpos( $request_uri, 'wp-cron.php' ) || false !== strpos( $request_uri, 'xmlrpc.php' ) ) {
			return;
		}

		// 2. Get User-Agent
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $user_agent ) ) {
			return;
		}

		// 3. Match against known bots (case-insensitive check)
		$matched_bot = '';
		foreach ( self::$bot_map as $ua_substring => $bot_slug ) {
			if ( false !== stripos( $user_agent, $ua_substring ) ) {
				$matched_bot = $bot_slug;
				break;
			}
		}

		if ( empty( $matched_bot ) ) {
			return;
		}

		// 4. Get and validate IP address
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		if ( ! $ip ) {
			return;
		}

		// 5. Verify IP address using Registry
		require_once MBL_PLUGIN_DIR . 'includes/class-mbl-ip-registry.php';
		$status = MBL_IP_Registry::verify_ip( $ip, $matched_bot );

		// 6. Log the request in database using $wpdb->insert
		global $wpdb;
		$table_name = $wpdb->prefix . 'mbl_bot_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'bot_name'            => $matched_bot,
				'user_agent'          => $user_agent,
				'ip_address'          => $ip,
				'requested_url'       => $request_uri,
				'timestamp'           => current_time( 'mysql', true ),
				'verification_status' => $status,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Daily cleanup cron job to delete old logs.
	 */
	public function daily_cleanup() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mbl_bot_logs';
		$retention_days = (int) get_option( 'mbl_retention_days', 30 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE timestamp < DATE_SUB(%s, INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			current_time( 'mysql', true ),
			$retention_days
		) );
	}
}
