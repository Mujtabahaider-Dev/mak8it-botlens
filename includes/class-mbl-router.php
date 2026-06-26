<?php
/**
 * Handles virtual URL routing.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Router class.
 */
class MBL_Router {

	/**
	 * Initialize the router hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'serve_virtual_endpoints' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'check_permalink_structure' ) );
		}
	}

	/**
	 * Register virtual route rewrite rules.
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?mbl_feed=llms', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?mbl_feed=llms-full', 'top' );
	}

	/**
	 * Register the custom feed query variable.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'mbl_feed';
		return $vars;
	}

	/**
	 * Intercept the template execution to serve plain-text endpoints.
	 */
	public function serve_virtual_endpoints() {
		$feed_type = get_query_var( 'mbl_feed' );

		if ( empty( $feed_type ) || ! in_array( $feed_type, array( 'llms', 'llms-full' ), true ) ) {
			return;
		}

		// Clear any existing output buffers to prevent theme/plugin output leak
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Set clean plain text header with UTF-8
		header( 'Content-Type: text/plain; charset=UTF-8' );

		require_once MBL_PLUGIN_DIR . 'includes/class-mbl-llms-generator.php';
		$generator = new MBL_LLMs_Generator();
		
		if ( 'llms-full' === $feed_type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text feed output, HTML escaping would corrupt sitemap markdown and URLs.
			echo $generator->get_llms_full_txt();
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text feed output, HTML escaping would corrupt sitemap markdown and URLs.
			echo $generator->get_llms_txt();
		}

		exit;
	}

	/**
	 * Detect plain permalinks and output a warning notice.
	 */
	public function check_permalink_structure() {
		if ( empty( get_option( 'permalink_structure' ) ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: Permalinks settings URL */
						wp_kses( __( '<strong>Mak8it BotLens Notice:</strong> Plain Permalinks are active. The dynamic <code>/llms.txt</code> and <code>/llms-full.txt</code> routes require pretty permalinks to resolve. Please update your <a href="%s">Permalink Settings</a>.', 'mak8it-botlens' ), array( 'strong' => array(), 'code' => array(), 'a' => array( 'href' => array() ) ) ),
						esc_url( admin_url( 'options-permalink.php' ) )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
