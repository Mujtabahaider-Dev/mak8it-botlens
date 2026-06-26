<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Mak8it_BotLens
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// 1. Drop database tables
$table_name = $wpdb->prefix . 'mbl_bot_logs';
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// 2. Delete all mbl_ prefixed options
delete_option( 'mbl_settings' );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'mbl_%' ) );

// 3. Delete all mbl_ prefixed transients
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_mbl_%' ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_mbl_%' ) );
// phpcs:enable

// 4. Clear all mbl_ cron events
wp_clear_scheduled_hook( 'mbl_daily_cleanup' );
wp_clear_scheduled_hook( 'mbl_weekly_ip_refresh' );
