<?php
/**
 * Fired during plugin activation.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activator class.
 */
class MBL_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		global $wpdb;

		// 1. Create database table
		$table_name = $wpdb->prefix . 'mbl_bot_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			bot_name varchar(50) NOT NULL,
			user_agent text NOT NULL,
			ip_address varchar(45) NOT NULL,
			requested_url text NOT NULL,
			timestamp datetime NOT NULL,
			verification_status varchar(20) NOT NULL,
			PRIMARY KEY  (id),
			KEY bot_name (bot_name),
			KEY timestamp (timestamp)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// 2. Set default options
		add_option( 'mbl_bot_settings', array() );
		add_option( 'mbl_retention_days', 30 );
		add_option( 'mbl_max_posts', 500 );
		add_option( 'mbl_db_version', '1.0.0' );

		// 3. Setup scheduled cron events
		if ( ! wp_next_scheduled( 'mbl_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'mbl_daily_cleanup' );
		}

		if ( ! wp_next_scheduled( 'mbl_weekly_ip_refresh' ) ) {
			wp_schedule_event( time(), 'weekly', 'mbl_weekly_ip_refresh' );
		}

		// 4. Setup rewrite rules and flush
		require_once MBL_PLUGIN_DIR . 'includes/class-mbl-router.php';
		$router = new MBL_Router();
		$router->register_rewrite_rules();
		flush_rewrite_rules();
	}
}
