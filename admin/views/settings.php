<?php
/**
 * Settings View for Mak8it BotLens.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

// Fetch settings from database
$bot_settings   = get_option( 'mbl_bot_settings', array() );
$retention_days = get_option( 'mbl_retention_days', 30 );
$max_posts      = get_option( 'mbl_max_posts', 500 );

$search_bots = array(
	'OAI-SearchBot' => __( 'OpenAI Search', 'mak8it-botlens' ),
	'PerplexityBot' => __( 'Perplexity Search', 'mak8it-botlens' ),
	'ChatGPT-User'  => __( 'OpenAI ChatGPT user requests', 'mak8it-botlens' ),
);

$training_bots = array(
	'GPTBot'             => __( 'OpenAI core crawler', 'mak8it-botlens' ),
	'ClaudeBot'          => __( 'Anthropic crawler', 'mak8it-botlens' ),
	'Google-Extended'    => __( 'Google training opt-out', 'mak8it-botlens' ),
	'Applebot'           => __( 'Apple Siri & AI crawler', 'mak8it-botlens' ),
	'CCBot'              => __( 'Common Crawl indexer', 'mak8it-botlens' ),
	'Meta-ExternalAgent' => __( 'Meta AI training crawler', 'mak8it-botlens' ),
);

require_once MBL_PLUGIN_DIR . 'admin/views/header.php';
?>

<!-- Headline -->
<div class="mbl-headline" style="margin-bottom: 24px;">
	<h2><?php esc_html_e( 'Settings', 'mak8it-botlens' ); ?></h2>
	<p><?php esc_html_e( 'Configure crawler visibility rules and logs settings.', 'mak8it-botlens' ); ?></p>
</div>

<!-- AJAX Action Notice Box -->
<div class="mbl-alert-box" id="mbl-settings-notice" style="display: none; margin-bottom: 24px;"></div>

<form id="mbl-settings-form" method="post" class="mbl-form-section">
	<?php wp_nonce_field( 'mbl_settings_nonce', 'mbl_nonce' ); ?>

	<div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">
		<!-- Section 1: Search & Citation Bots -->
		<div class="mbl-card" style="flex: 1 1 calc(50% - 12px); min-width: 350px; display: flex; flex-direction: column;">
			<h3>
				<span class="mbl-material-icon" style="color: var(--mbl-primary); font-size: 20px;">search</span>
				<span><?php esc_html_e( 'Search & Citation Bots', 'mak8it-botlens' ); ?></span>
			</h3>
			<p style="font-size: 13px; color: var(--mbl-text-muted); margin: -8px 0 20px 0; line-height: 1.5;">
				<?php esc_html_e( 'These crawlers compile citations and links to display directly in AI search engine results. Blocking them may prevent your site from appearing as an answers source.', 'mak8it-botlens' ); ?>
			</p>
			
			<div style="display: flex; flex-direction: column; gap: 12px;">
				<?php
				foreach ( $search_bots as $bot_name => $bot_purpose ) :
					$is_blocked = isset( $bot_settings['block_search_bots'][ $bot_name ] ) && 1 === (int) $bot_settings['block_search_bots'][ $bot_name ];
					?>
					<div class="mbl-settings-row">
						<div class="mbl-settings-row-info">
							<h4 class="mbl-settings-row-title"><?php echo esc_html( $bot_name ); ?></h4>
							<p class="mbl-settings-row-desc"><?php echo esc_html( $bot_purpose ); ?></p>
						</div>
						<label class="mbl-switch">
							<input type="hidden" name="settings[block_search_bots][<?php echo esc_attr( $bot_name ); ?>]" value="0">
							<input type="checkbox" id="mbl-bot-<?php echo esc_attr( $bot_name ); ?>" name="settings[block_search_bots][<?php echo esc_attr( $bot_name ); ?>]" value="1" <?php checked( true, $is_blocked ); ?>>
							<span class="mbl-switch-slider"></span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Section 2: AI Training & Scraper Bots -->
		<div class="mbl-card" style="flex: 1 1 calc(50% - 12px); min-width: 350px; display: flex; flex-direction: column;">
			<h3>
				<span class="mbl-material-icon" style="color: var(--mbl-primary); font-size: 20px;">psychology</span>
				<span><?php esc_html_e( 'AI Training & Scraper Bots', 'mak8it-botlens' ); ?></span>
			</h3>
			<p style="font-size: 13px; color: var(--mbl-text-muted); margin: -8px 0 20px 0; line-height: 1.5;">
				<?php esc_html_e( 'These crawlers ingest and store your website content to train large language models. They generally do not provide direct attribution or traffic referral to your site.', 'mak8it-botlens' ); ?>
			</p>
			
			<div style="display: flex; flex-direction: column; gap: 12px;">
				<?php
				foreach ( $training_bots as $bot_name => $bot_purpose ) :
					$is_blocked = isset( $bot_settings['block_training_bots'][ $bot_name ] ) && 1 === (int) $bot_settings['block_training_bots'][ $bot_name ];
					?>
					<div class="mbl-settings-row">
						<div class="mbl-settings-row-info">
							<h4 class="mbl-settings-row-title"><?php echo esc_html( $bot_name ); ?></h4>
							<p class="mbl-settings-row-desc"><?php echo esc_html( $bot_purpose ); ?></p>
						</div>
						<label class="mbl-switch">
							<input type="hidden" name="settings[block_training_bots][<?php echo esc_attr( $bot_name ); ?>]" value="0">
							<input type="checkbox" id="mbl-bot-<?php echo esc_attr( $bot_name ); ?>" name="settings[block_training_bots][<?php echo esc_attr( $bot_name ); ?>]" value="1" <?php checked( true, $is_blocked ); ?>>
							<span class="mbl-switch-slider"></span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Section 3: General Options & Data Management -->
		<div class="mbl-card" style="flex: 1 1 100%;">
			<h3>
				<span class="mbl-material-icon" style="color: var(--mbl-primary); font-size: 20px;">settings</span>
				<span><?php esc_html_e( 'General Configuration', 'mak8it-botlens' ); ?></span>
			</h3>
			<p style="font-size: 13px; color: var(--mbl-text-muted); margin: -8px 0 24px 0; line-height: 1.5;">
				<?php esc_html_e( 'Configure sitemaps data limit and database logs expiration limits.', 'mak8it-botlens' ); ?>
			</p>

			<div style="display: flex; flex-wrap: wrap; gap: 24px;">
				<div class="mbl-form-group" style="flex: 1 1 calc(50% - 12px); min-width: 280px;">
					<label for="mbl-max-posts-input"><?php esc_html_e( 'Sitemap Maximum Posts Limit', 'mak8it-botlens' ); ?></label>
					<input type="number" id="mbl-max-posts-input" name="mbl_max_posts" class="mbl-input" value="<?php echo esc_attr( $max_posts ); ?>" min="10" max="2000" required>
					<p class="mbl-settings-row-desc" style="margin-top: 4px;"><?php esc_html_e( 'Maximum number of publications parsed inside the llms.txt virtual index feeds.', 'mak8it-botlens' ); ?></p>
				</div>

				<div class="mbl-form-group" style="flex: 1 1 calc(50% - 12px); min-width: 280px;">
					<label for="mbl-retention-input"><?php esc_html_e( 'Logs Retention Period (Days)', 'mak8it-botlens' ); ?></label>
					<input type="number" id="mbl-retention-input" name="mbl_retention_days" class="mbl-input" value="<?php echo esc_attr( $retention_days ); ?>" min="1" max="90" required>
					<p class="mbl-settings-row-desc" style="margin-top: 4px;"><?php esc_html_e( 'Logs older than this threshold will be pruned automatically on a daily basis.', 'mak8it-botlens' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Form Submit Actions Group -->
	<div style="display: flex; gap: 12px; margin-top: 8px;">
		<button type="submit" class="mbl-btn mbl-btn-primary mbl-btn-large" id="mbl-save-settings-btn">
			<?php esc_html_e( 'Save Settings', 'mak8it-botlens' ); ?>
		</button>
		<button type="button" class="mbl-btn mbl-btn-danger mbl-btn-large" id="mbl-clear-logs-btn">
			<?php esc_html_e( 'Clear All Logs', 'mak8it-botlens' ); ?>
		</button>
	</div>
</form>

<?php require_once MBL_PLUGIN_DIR . 'admin/views/footer.php'; ?>
