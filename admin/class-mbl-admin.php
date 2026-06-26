<?php
/**
 * Admin Class for Mak8it BotLens.
 * Handles the admin dashboard, settings, and logs menu pages.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI controller class.
 */
class MBL_Admin {

	/**
	 * Hook admin actions.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX actions
		add_action( 'wp_ajax_mbl_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mbl_clear_logs', array( $this, 'ajax_clear_logs' ) );
	}

	/**
	 * Register menu and submenu pages.
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'Mak8it BotLens', 'mak8it-botlens' ),
			__( 'BotLens', 'mak8it-botlens' ),
			'manage_options',
			'mak8it-botlens',
			array( $this, 'render_dashboard_page' ),
			'dashicons-shield',
			80
		);

		add_submenu_page(
			'mak8it-botlens',
			__( 'Dashboard', 'mak8it-botlens' ),
			__( 'Dashboard', 'mak8it-botlens' ),
			'manage_options',
			'mak8it-botlens',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'mak8it-botlens',
			__( 'Crawler Logs', 'mak8it-botlens' ),
			__( 'Crawler Logs', 'mak8it-botlens' ),
			'manage_options',
			'mak8it-botlens-logs',
			array( $this, 'render_logs_page' )
		);

		add_submenu_page(
			'mak8it-botlens',
			__( 'Settings', 'mak8it-botlens' ),
			__( 'Settings', 'mak8it-botlens' ),
			'manage_options',
			'mak8it-botlens-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue administration CSS and JS.
	 *
	 * @param string $hook Page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'mak8it-botlens' ) ) {
			return;
		}

		// Enqueue compiled Tailwind CSS with local fonts
		wp_enqueue_style(
			'mbl-tailwind',
			MBL_PLUGIN_URL . 'admin/assets/css/tailwind-compiled.css',
			array(),
			MBL_VERSION
		);

		// Enqueue toast notice styles
		wp_enqueue_style(
			'mbl-toast',
			MBL_PLUGIN_URL . 'admin/assets/css/toast-notice.css',
			array(),
			MBL_VERSION
		);

		// Enqueue custom admin styles
		wp_enqueue_style(
			'mbl-admin-css',
			MBL_PLUGIN_URL . 'admin/assets/admin.css',
			array( 'mbl-tailwind', 'mbl-toast' ),
			MBL_VERSION
		);

		// Enqueue admin scripts
		wp_enqueue_script(
			'mbl-admin-js',
			MBL_PLUGIN_URL . 'admin/assets/admin.js',
			array( 'jquery' ),
			MBL_VERSION,
			true
		);

		wp_localize_script( 'mbl-admin-js', 'mblAdmin', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'settings_nonce' => wp_create_nonce( 'mbl_settings_nonce' ),
			'clear_nonce'    => wp_create_nonce( 'mbl_clear_logs_nonce' ),
		) );
	}

	/**
	 * Render Dashboard view.
	 */
	public function render_dashboard_page() {
		require_once MBL_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render Logs view.
	 */
	public function render_logs_page() {
		require_once MBL_PLUGIN_DIR . 'admin/views/logs.php';
	}

	/**
	 * Render Settings view.
	 */
	public function render_settings_page() {
		require_once MBL_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * AJAX endpoint to save settings.
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'mbl_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mak8it-botlens' ) ) );
		}

		$raw_settings = isset( $_POST['settings'] ) ? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : array();

		// Sanitize bot settings
		$settings = array();
		
		$settings['block_search_bots'] = array();
		$search_bots = array( 'OAI-SearchBot', 'PerplexityBot', 'ChatGPT-User' );
		foreach ( $search_bots as $bot ) {
			$settings['block_search_bots'][ $bot ] = isset( $raw_settings['block_search_bots'][ $bot ] ) ? absint( $raw_settings['block_search_bots'][ $bot ] ) : 0;
		}

		$settings['block_training_bots'] = array();
		$training_bots = array( 'GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot', 'CCBot', 'Meta-ExternalAgent' );
		foreach ( $training_bots as $bot ) {
			$settings['block_training_bots'][ $bot ] = isset( $raw_settings['block_training_bots'][ $bot ] ) ? absint( $raw_settings['block_training_bots'][ $bot ] ) : 0;
		}

		update_option( 'mbl_bot_settings', $settings );
		update_option( 'mbl_setup_completed', 1 );

		// General settings
		if ( isset( $_POST['mbl_retention_days'] ) ) {
			update_option( 'mbl_retention_days', absint( wp_unslash( $_POST['mbl_retention_days'] ) ) );
		}
		if ( isset( $_POST['mbl_max_posts'] ) ) {
			update_option( 'mbl_max_posts', absint( wp_unslash( $_POST['mbl_max_posts'] ) ) );
		}

		// Bust LLMs sitemap caches
		delete_transient( 'mbl_llms_txt_cache' );
		delete_transient( 'mbl_llms_full_cache' );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'mak8it-botlens' ) ) );
	}

	/**
	 * AJAX endpoint to clear logs.
	 */
	public function ajax_clear_logs() {
		check_ajax_referer( 'mbl_clear_logs_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mak8it-botlens' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'mbl_bot_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		wp_send_json_success( array( 'message' => __( 'Logs cleared successfully.', 'mak8it-botlens' ) ) );
	}
}
