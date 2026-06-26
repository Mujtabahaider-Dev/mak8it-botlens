<?php
/**
 * Dashboard View for Mak8it BotLens.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'mbl_bot_logs';

$seven_days_ago = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );

$total_requests      = 0;
$verified_requests   = 0;
$unverified_requests = 0;
$spoofed_requests    = 0;
$recent_logs         = array();
$any_logs_exist      = false;

// Verify if database table exists before querying
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

if ( $table_exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$total_requests = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$seven_days_ago
	) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$verified_requests = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_name} WHERE verification_status = %s AND timestamp >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		'verified',
		$seven_days_ago
	) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$unverified_requests = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_name} WHERE verification_status = %s AND timestamp >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		'unverified',
		$seven_days_ago
	) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$spoofed_requests = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_name} WHERE verification_status = %s AND timestamp >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		'spoofed',
		$seven_days_ago
	) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$recent_logs = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table_name} ORDER BY timestamp DESC LIMIT 10" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$total_db_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} LIMIT 1" );
	$any_logs_exist = ( $total_db_count > 0 );
}

$setup_completed = (int) get_option( 'mbl_setup_completed', 0 );

// Fetch bot settings for sidebar status indicators
$bot_settings = get_option( 'mbl_bot_settings', array() );

$search_bots = array(
	'OAI-SearchBot' => 'Search',
	'PerplexityBot' => 'Search',
	'ChatGPT-User'  => 'Search',
);

$training_bots = array(
	'GPTBot'             => 'Training',
	'ClaudeBot'          => 'Training',
	'Google-Extended'    => 'Training',
	'Applebot'           => 'Training',
	'CCBot'              => 'Training',
	'Meta-ExternalAgent' => 'Training',
);

require_once MBL_PLUGIN_DIR . 'admin/views/header.php';
?>

<!-- Headline -->
<div class="mbl-headline" style="margin-bottom: 24px;">
	<h2><?php esc_html_e( 'Dashboard', 'mak8it-botlens' ); ?></h2>
	<p><?php esc_html_e( 'Overview of AI crawlers visibility, statistics, and access rules.', 'mak8it-botlens' ); ?></p>
</div>

<!-- Metrics Grid -->
<div class="mbl-grid-4">
	<!-- Card 1: Total Crawls -->
	<div class="mbl-stat-card total">
		<span class="mbl-material-icon mbl-stat-icon-bg">query_stats</span>
		<p class="mbl-stat-label"><?php esc_html_e( 'Total Crawls (7 Days)', 'mak8it-botlens' ); ?></p>
		<h3 class="mbl-stat-value"><?php echo esc_html( number_format( $total_requests ) ); ?></h3>
		<div class="mbl-stat-footer">
			<span class="mbl-material-icon" style="font-size: 16px;">trending_up</span>
			<span><?php esc_html_e( 'Crawler Traffic', 'mak8it-botlens' ); ?></span>
		</div>
	</div>

	<!-- Card 2: Verified Bots -->
	<div class="mbl-stat-card verified">
		<span class="mbl-material-icon mbl-stat-icon-bg">verified</span>
		<p class="mbl-stat-label"><?php esc_html_e( 'Verified Bots', 'mak8it-botlens' ); ?></p>
		<h3 class="mbl-stat-value"><?php echo esc_html( number_format( $verified_requests ) ); ?></h3>
		<div class="mbl-stat-footer">
			<span class="mbl-material-icon" style="font-size: 16px;">check_circle</span>
			<span><?php esc_html_e( 'Legitimate Crawlers', 'mak8it-botlens' ); ?></span>
		</div>
	</div>

	<!-- Card 3: Unverified Crawls -->
	<div class="mbl-stat-card unverified">
		<span class="mbl-material-icon mbl-stat-icon-bg">help_outline</span>
		<p class="mbl-stat-label"><?php esc_html_e( 'Unverified Crawls', 'mak8it-botlens' ); ?></p>
		<h3 class="mbl-stat-value"><?php echo esc_html( number_format( $unverified_requests ) ); ?></h3>
		<div class="mbl-stat-footer">
			<span class="mbl-material-icon" style="font-size: 16px;">info</span>
			<span><?php esc_html_e( 'Needs Validation', 'mak8it-botlens' ); ?></span>
		</div>
	</div>

	<!-- Card 4: Spoofed Requests -->
	<div class="mbl-stat-card spoofed">
		<span class="mbl-material-icon mbl-stat-icon-bg">gpp_bad</span>
		<p class="mbl-stat-label"><?php esc_html_e( 'Spoofed Requests', 'mak8it-botlens' ); ?></p>
		<h3 class="mbl-stat-value"><?php echo esc_html( number_format( $spoofed_requests ) ); ?></h3>
		<div class="mbl-stat-footer">
			<span class="mbl-material-icon" style="font-size: 16px;">dangerous</span>
			<span><?php esc_html_e( 'Crawler Warnings', 'mak8it-botlens' ); ?></span>
		</div>
	</div>
</div>

<!-- Onboarding Modal Popup -->
<?php if ( ! $any_logs_exist && 0 === $setup_completed ) : ?>
	<div class="mbl-modal-backdrop" id="mbl-onboarding-modal">
		<div class="mbl-modal-card">
			<button type="button" class="mbl-modal-close" id="mbl-modal-close-btn" aria-label="<?php esc_attr_e( 'Dismiss', 'mak8it-botlens' ); ?>">
				<span class="mbl-material-icon">close</span>
			</button>
			<div class="mbl-modal-body">
				<div class="mbl-modal-icon-container">
					<span class="mbl-material-icon mbl-pulse-icon">gpp_maybe</span>
				</div>
				<h2><?php esc_html_e( 'Welcome to Mak8it BotLens', 'mak8it-botlens' ); ?></h2>
				<p><?php esc_html_e( 'Monitor and control AI search crawlers on your website. Get started by configuring your scraper blocking rules.', 'mak8it-botlens' ); ?></p>
				<div class="mbl-modal-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-settings' ) ); ?>" class="mbl-btn mbl-btn-primary mbl-btn-large">
						<?php esc_html_e( 'Configure Settings', 'mak8it-botlens' ); ?>
					</a>
					<button type="button" class="mbl-btn mbl-btn-secondary mbl-btn-large" id="mbl-modal-skip-btn">
						<?php esc_html_e( 'Skip for Now', 'mak8it-botlens' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<script>
		if ( localStorage.getItem( 'mbl_modal_dismissed' ) === '1' ) {
			var mblModal = document.getElementById( 'mbl-onboarding-modal' );
			if ( mblModal ) {
				mblModal.style.display = 'none';
				mblModal.parentNode.removeChild( mblModal );
			}
		}
	</script>
<?php endif; ?>

<!-- Two Column Layout Grid -->
<div class="mbl-layout-columns">
	<!-- Left: Recent Activity Table -->
	<div class="mbl-column-main">
		<div class="mbl-table-card">
			<div class="mbl-table-card-header">
				<h3 class="mbl-table-title"><?php esc_html_e( 'Recent AI Crawler Activity', 'mak8it-botlens' ); ?></h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-logs' ) ); ?>" class="mbl-btn mbl-btn-secondary" style="height: 32px; padding: 0 14px; font-size: 12px;">
					<?php esc_html_e( 'View All Logs', 'mak8it-botlens' ); ?>
				</a>
			</div>
			<table class="mbl-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Bot Name', 'mak8it-botlens' ); ?></th>
						<th><?php esc_html_e( 'Status', 'mak8it-botlens' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'mak8it-botlens' ); ?></th>
						<th><?php esc_html_e( 'Requested URL', 'mak8it-botlens' ); ?></th>
						<th><?php esc_html_e( 'Time (UTC)', 'mak8it-botlens' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $recent_logs ) ) : ?>
						<?php foreach ( $recent_logs as $log ) : ?>
							<tr>
								<td class="mbl-table-bot"><?php echo esc_html( strtoupper( $log->bot_name ) ); ?></td>
								<td>
									<?php if ( 'verified' === $log->verification_status ) : ?>
										<span class="mbl-badge verified">
											<span class="mbl-badge-dot"></span>
											<?php esc_html_e( 'Verified', 'mak8it-botlens' ); ?>
										</span>
									<?php elseif ( 'unverified' === $log->verification_status ) : ?>
										<span class="mbl-badge unverified">
											<span class="mbl-badge-dot"></span>
											<?php esc_html_e( 'Unverified', 'mak8it-botlens' ); ?>
										</span>
									<?php else : ?>
										<span class="mbl-badge spoofed">
											<span class="mbl-badge-dot"></span>
											<?php esc_html_e( 'Spoofed', 'mak8it-botlens' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="mbl-table-ip"><?php echo esc_html( $log->ip_address ); ?></td>
								<td class="mbl-table-url">
									<a href="<?php echo esc_url( home_url( $log->requested_url ) ); ?>" target="_blank">
										<?php echo esc_html( $log->requested_url ); ?>
									</a>
								</td>
								<td style="color: var(--mbl-text-muted);"><?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $log->timestamp ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" style="text-align: center; padding: 40px; color: var(--mbl-text-muted); font-weight: 500;">
								<?php esc_html_e( 'No recent AI crawler requests found.', 'mak8it-botlens' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Right: Sidebar Status / Quick Links -->
	<div class="mbl-column-sidebar">
		<!-- Active Bot Status -->
		<div class="mbl-card">
			<h3>
				<span class="mbl-material-icon" style="color: var(--mbl-primary); font-size: 20px;">visibility</span>
				<span><?php esc_html_e( 'Active Bot Status', 'mak8it-botlens' ); ?></span>
			</h3>
			<div class="mbl-status-list">
				<?php
				$all_bots = array_merge( $search_bots, $training_bots );
				foreach ( $all_bots as $bot_name => $bot_type ) :
					// Check if bot is blocked (toggle on = 1 = Blocked)
					$is_blocked = false;
					if ( 'Search' === $bot_type ) {
						$is_blocked = isset( $bot_settings['block_search_bots'][ $bot_name ] ) && 1 === (int) $bot_settings['block_search_bots'][ $bot_name ];
					} else {
						$is_blocked = isset( $bot_settings['block_training_bots'][ $bot_name ] ) && 1 === (int) $bot_settings['block_training_bots'][ $bot_name ];
					}
					?>
					<div class="mbl-status-row <?php echo $is_blocked ? 'blocked' : ''; ?>">
						<div class="mbl-status-bot-info">
							<span class="mbl-status-dot-wrap">
								<?php if ( ! $is_blocked ) : ?>
									<span class="mbl-pulse-dot"></span>
								<?php endif; ?>
								<span class="mbl-status-dot"></span>
							</span>
							<span class="mbl-status-bot-name"><?php echo esc_html( $bot_name ); ?></span>
						</div>
						
						<?php if ( 'Search' === $bot_type ) : ?>
							<span class="mbl-badge search"><?php esc_html_e( 'Search', 'mak8it-botlens' ); ?></span>
						<?php else : ?>
							<span class="mbl-badge training"><?php esc_html_e( 'Training', 'mak8it-botlens' ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Quick Links -->
		<div class="mbl-card">
			<h3>
				<span class="mbl-material-icon" style="color: var(--mbl-primary); font-size: 20px;">link</span>
				<span><?php esc_html_e( 'Quick Links', 'mak8it-botlens' ); ?></span>
			</h3>
			<div class="mbl-link-list">
				<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" class="mbl-link-item">
					<span class="mbl-link-left">
						<span class="mbl-material-icon">link</span>
						<span><?php esc_html_e( 'View llms.txt', 'mak8it-botlens' ); ?></span>
					</span>
					<span class="mbl-material-icon mbl-link-arrow">arrow_forward</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" class="mbl-link-item">
					<span class="mbl-link-left">
						<span class="mbl-material-icon">link</span>
						<span><?php esc_html_e( 'View llms-full.txt', 'mak8it-botlens' ); ?></span>
					</span>
					<span class="mbl-material-icon mbl-link-arrow">arrow_forward</span>
				</a>
				<a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" class="mbl-link-item">
					<span class="mbl-link-left">
						<span class="mbl-material-icon">description</span>
						<span><?php esc_html_e( 'View robots.txt', 'mak8it-botlens' ); ?></span>
					</span>
					<span class="mbl-material-icon mbl-link-arrow">arrow_forward</span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-settings' ) ); ?>" class="mbl-link-item">
					<span class="mbl-link-left">
						<span class="mbl-material-icon">settings</span>
						<span><?php esc_html_e( 'Configure Settings', 'mak8it-botlens' ); ?></span>
					</span>
					<span class="mbl-material-icon mbl-link-arrow">arrow_forward</span>
				</a>
			</div>
		</div>
	</div>
</div>

<?php require_once MBL_PLUGIN_DIR . 'admin/views/footer.php'; ?>
