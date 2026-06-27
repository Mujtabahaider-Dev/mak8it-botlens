<?php
/**
 * Plugin Name:       Mak8it BotLens
 * Plugin URI:        https://mak8it.com/botlens
 * Description:       AI crawler visibility and control for WordPress. IP-verified bot tracking, llms.txt generation, and smart robots.txt management.
 * Version:           1.0.0
 * Author:            Mak8it
 * Author URI:        https://mak8it.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mak8it-botlens
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

// Define core constants
define( 'MBL_VERSION', '1.0.0' );
define( 'MBL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation logic.
 */
function mbl_activate() {
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-activator.php';
	MBL_Activator::activate();
}
register_activation_hook( __FILE__, 'mbl_activate' );

/**
 * Deactivation logic.
 */
function mbl_deactivate() {
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-deactivator.php';
	MBL_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'mbl_deactivate' );

/**
 * Orchestrate and run the plugin.
 */
function mbl_run_plugin() {
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-router.php';
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-llms-generator.php';
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-bot-tracker.php';
	require_once MBL_PLUGIN_DIR . 'includes/class-mbl-robots-controller.php';

	$router = new MBL_Router();
	$router->init();

	$generator = new MBL_LLMs_Generator();
	$generator->init();

	$tracker = new MBL_Bot_Tracker();
	$tracker->init();

	$robots = new MBL_Robots_Controller();
	$robots->init();

	if ( is_admin() ) {
		require_once MBL_PLUGIN_DIR . 'admin/class-mbl-admin.php';
		$admin = new MBL_Admin();
		$admin->init();
	}
}
add_action( 'plugins_loaded', 'mbl_run_plugin' );
