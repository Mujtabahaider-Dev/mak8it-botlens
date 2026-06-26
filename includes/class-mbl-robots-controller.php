<?php
/**
 * Handles dynamic generation of robots.txt rules for AI bots.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * Robots Controller class.
 */
class MBL_Robots_Controller {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'robots_txt', array( $this, 'inject_rules' ), 100, 2 );
	}

	/**
	 * Inject AI bot blocking rules into robots.txt.
	 *
	 * @param string $output Existing robots.txt rules.
	 * @param bool   $public Whether the site is public.
	 * @return string Modified robots.txt rules.
	 */
	public function inject_rules( $output, $public ) {
		// If site is not public, let WordPress block everything by default
		if ( ! $public ) {
			return $output;
		}

		$settings = get_option( 'mbl_bot_settings', array() );
		$block_rules = '';

		// Search Bots
		$search_bots = array( 'OAI-SearchBot', 'PerplexityBot', 'ChatGPT-User' );
		foreach ( $search_bots as $bot ) {
			if ( isset( $settings['block_search_bots'][ $bot ] ) && 1 === (int) $settings['block_search_bots'][ $bot ] ) {
				$block_rules .= "\n# Block " . $bot . "\n";
				$block_rules .= "User-agent: " . $bot . "\n";
				$block_rules .= "Disallow: /\n";
			}
		}

		// Training Bots
		$training_bots = array( 'GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot', 'CCBot', 'Meta-ExternalAgent' );
		foreach ( $training_bots as $bot ) {
			if ( isset( $settings['block_training_bots'][ $bot ] ) && 1 === (int) $settings['block_training_bots'][ $bot ] ) {
				$block_rules .= "\n# Block " . $bot . "\n";
				$block_rules .= "User-agent: " . $bot . "\n";
				$block_rules .= "Disallow: /\n";
			}
		}

		if ( ! empty( $block_rules ) ) {
			$output .= "\n# ==========================================\n";
			$output .= "# Mak8it BotLens - AI Bot Control Rules\n";
			$output .= "# ==========================================\n";
			$output .= $block_rules;
		}

		return $output;
	}
}
