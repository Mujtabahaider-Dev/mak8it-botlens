<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Deactivator class.
 */
class MBL_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// 1. Flush rewrite rules
		flush_rewrite_rules();

		// 2. Clear scheduled cron events
		wp_clear_scheduled_hook( 'mbl_daily_cleanup' );
		wp_clear_scheduled_hook( 'mbl_weekly_ip_refresh' );

		// 3. Delete transients
		delete_transient( 'mbl_llms_txt_cache' );
		delete_transient( 'mbl_llms_full_cache' );
	}
}
