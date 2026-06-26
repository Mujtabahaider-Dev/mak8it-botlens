<?php
/**
 * Generates llms.txt and llms-full.txt sitemap feeds.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMs Generator class.
 */
class MBL_LLMs_Generator {

	/**
	 * Initialize the generator hooks.
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'bust_cache' ) );
		add_action( 'delete_post', array( $this, 'bust_cache' ) );
		add_action( 'transition_post_status', array( $this, 'bust_cache_status' ), 10, 3 );
	}

	/**
	 * Get llms.txt content.
	 *
	 * @return string Content.
	 */
	public function get_llms_txt() {
		$cached = get_transient( 'mbl_llms_txt_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$output = $this->build_feed( false );
		set_transient( 'mbl_llms_txt_cache', $output, DAY_IN_SECONDS );
		return $output;
	}

	/**
	 * Get llms-full.txt content.
	 *
	 * @return string Content.
	 */
	public function get_llms_full_txt() {
		$cached = get_transient( 'mbl_llms_full_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$output = $this->build_feed( true );
		set_transient( 'mbl_llms_full_cache', $output, DAY_IN_SECONDS );
		return $output;
	}

	/**
	 * Helper function for backward compatibility.
	 *
	 * @param string $type The feed type.
	 * @return string Content.
	 */
	public function generate( $type ) {
		if ( 'llms-full' === $type ) {
			return $this->get_llms_full_txt();
		}
		return $this->get_llms_txt();
	}

	/**
	 * Build the markdown content.
	 *
	 * @param bool $include_full_content True to include full content, false for description only.
	 * @return string Markdown output.
	 */
	private function build_feed( $include_full_content ) {
		require_once MBL_PLUGIN_DIR . 'includes/class-mbl-seo-bridge.php';

		$site_title = get_option( 'blogname' );
		$custom_desc = get_option( 'blogdescription' );

		// Start markdown document
		$markdown = '# ' . $site_title . "\n";
		if ( ! empty( $custom_desc ) ) {
			$markdown .= '> ' . $custom_desc . "\n\n";
		}

		$max_posts = (int) get_option( 'mbl_max_posts', 500 );

		$query = new WP_Query( array(
			'post_type'      => array( 'page', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => $max_posts,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		) );

		$pages_markdown = '';
		$posts_markdown = '';

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $p ) {
				// Exclude noindex posts via SEO Bridge
				if ( MBL_SEO_Bridge::is_post_noindex( $p->ID ) ) {
					continue;
				}

				$url = get_permalink( $p->ID );
				$title = html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' );
				$description = html_entity_decode( MBL_SEO_Bridge::get_post_description( $p->ID ), ENT_QUOTES, 'UTF-8' );

				$item_md = '- [' . $title . '](' . $url . '): ' . $description . "\n";

				if ( $include_full_content ) {
					$content = get_post_field( 'post_content', $p->ID );
					$content = strip_shortcodes( $content );

					// Replace block closures with spaces to avoid adjacent text merging (e.g. NileAmazon -> Nile Amazon)
					$block_tags = array( '</p>', '</div>', '</li>', '</td>', '</tr>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '<br>', '<br />', '<br/>' );
					$content = str_replace( $block_tags, ' ', $content );

					$content = wp_strip_all_tags( $content );
					$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
					$content = trim( preg_replace( '/\s+/', ' ', $content ) );

					if ( ! empty( $content ) ) {
						$item_md .= "\n  " . $content . "\n\n";
					}
				}

				if ( 'page' === $p->post_type ) {
					$pages_markdown .= $item_md;
				} else {
					$posts_markdown .= $item_md;
				}
			}
		}

		wp_reset_postdata();

		// Append pages section
		$markdown .= "## Pages\n";
		if ( ! empty( $pages_markdown ) ) {
			$markdown .= $pages_markdown;
		} else {
			$markdown .= "No pages found.\n";
		}
		$markdown .= "\n";

		// Append posts section
		$markdown .= "## Posts\n";
		if ( ! empty( $posts_markdown ) ) {
			$markdown .= $posts_markdown;
		} else {
			$markdown .= "No posts found.\n";
		}

		return $markdown;
	}

	/**
	 * Clear sitemaps cache.
	 */
	public function bust_cache() {
		delete_transient( 'mbl_llms_txt_cache' );
		delete_transient( 'mbl_llms_full_cache' );
	}

	/**
	 * Clear cache on status transition.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post Post object.
	 */
	public function bust_cache_status( $new_status, $old_status, $post ) {
		$this->bust_cache();
	}
}
