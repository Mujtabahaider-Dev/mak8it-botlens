<?php
/**
 * Logs View for Mak8it BotLens.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'mbl_bot_logs';

// Check if database table exists before querying
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

$logs          = array();
$bot_list      = array();
$total_records = 0;
$per_page      = 20;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for logs page navigation, no state changes.
$current_page  = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$total_pages   = 0;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for log filters, no state changes.
$filter_bot    = isset( $_GET['bot'] ) ? sanitize_text_field( wp_unslash( $_GET['bot'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for log filters, no state changes.
$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for log filters, no state changes.
$start_date    = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for log filters, no state changes.
$end_date      = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

if ( $table_exists ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$bot_list = $wpdb->get_col( "SELECT DISTINCT bot_name FROM {$table_name} ORDER BY bot_name ASC" );

	$where_clauses = array();
	$query_args    = array();

	if ( ! empty( $filter_bot ) ) {
		$where_clauses[] = 'bot_name = %s';
		$query_args[]    = $filter_bot;
	}

	if ( ! empty( $filter_status ) ) {
		$where_clauses[] = 'verification_status = %s';
		$query_args[]    = $filter_status;
	}

	if ( ! empty( $start_date ) ) {
		$where_clauses[] = 'timestamp >= %s';
		$query_args[]    = $start_date . ' 00:00:00';
	}

	if ( ! empty( $end_date ) ) {
		$where_clauses[] = 'timestamp <= %s';
		$query_args[]    = $end_date . ' 23:59:59';
	}

	$where_sql = '';
	if ( ! empty( $where_clauses ) ) {
		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
	}

	// Count total matching records
	$count_query = "SELECT COUNT(*) FROM {$table_name} {$where_sql}";
	if ( ! empty( $query_args ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_records = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $query_args ) );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_records = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	$total_pages  = ceil( $total_records / $per_page );
	$current_page = min( $current_page, max( 1, $total_pages ) );
	$offset       = ( $current_page - 1 ) * $per_page;

	// Query paginated log entries
	$data_query = "SELECT * FROM {$table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
	$final_args = $query_args;
	$final_args[] = $per_page;
	$final_args[] = $offset;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$logs = $wpdb->get_results( $wpdb->prepare( $data_query, $final_args ) );
}

require_once MBL_PLUGIN_DIR . 'admin/views/header.php';
?>

<!-- Headline -->
<div class="mbl-headline" style="margin-bottom: 24px;">
	<h2><?php esc_html_e( 'Crawler Logs', 'mak8it-botlens' ); ?></h2>
	<p><?php printf( esc_html( _n( 'Review and filter history of %d crawled asset.', 'Review and filter history of %d crawled assets.', $total_records, 'mak8it-botlens' ) ), intval( $total_records ) ); ?></p>
</div>

<!-- Log Filter Toolbar -->
<div class="mbl-filter-card">
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="mbl-filter-form" id="mbl-logs-filter-form">
		<input type="hidden" name="page" value="mak8it-botlens-logs">

		<div class="mbl-filter-group">
			<label for="mbl-filter-bot"><?php esc_html_e( 'Crawler:', 'mak8it-botlens' ); ?></label>
			<select id="mbl-filter-bot" name="bot" class="mbl-filter-select">
				<option value=""><?php esc_html_e( 'All tracked bots', 'mak8it-botlens' ); ?></option>
				<?php foreach ( $bot_list as $bot ) : ?>
					<option value="<?php echo esc_attr( $bot ); ?>" <?php selected( $filter_bot, $bot ); ?>><?php echo esc_html( strtoupper( $bot ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="mbl-filter-group">
			<label for="mbl-filter-status"><?php esc_html_e( 'Status:', 'mak8it-botlens' ); ?></label>
			<select id="mbl-filter-status" name="status" class="mbl-filter-select">
				<option value=""><?php esc_html_e( 'All states', 'mak8it-botlens' ); ?></option>
				<option value="verified" <?php selected( $filter_status, 'verified' ); ?>><?php esc_html_e( 'Verified', 'mak8it-botlens' ); ?></option>
				<option value="unverified" <?php selected( $filter_status, 'unverified' ); ?>><?php esc_html_e( 'Unverified', 'mak8it-botlens' ); ?></option>
				<option value="spoofed" <?php selected( $filter_status, 'spoofed' ); ?>><?php esc_html_e( 'Spoofed', 'mak8it-botlens' ); ?></option>
			</select>
		</div>

		<div class="mbl-filter-group">
			<label for="mbl-filter-start"><?php esc_html_e( 'From:', 'mak8it-botlens' ); ?></label>
			<input type="date" id="mbl-filter-start" name="start_date" class="mbl-filter-input" value="<?php echo esc_attr( $start_date ); ?>">
		</div>

		<div class="mbl-filter-group">
			<label for="mbl-filter-end"><?php esc_html_e( 'To:', 'mak8it-botlens' ); ?></label>
			<input type="date" id="mbl-filter-end" name="end_date" class="mbl-filter-input" value="<?php echo esc_attr( $end_date ); ?>">
		</div>

		<div class="mbl-filter-actions">
			<button type="submit" class="mbl-btn mbl-btn-primary">
				<?php esc_html_e( 'Filter', 'mak8it-botlens' ); ?>
			</button>
			<?php if ( ! empty( $filter_bot ) || ! empty( $filter_status ) || ! empty( $start_date ) || ! empty( $end_date ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-logs' ) ); ?>" class="mbl-btn mbl-btn-danger">
					<?php esc_html_e( 'Reset', 'mak8it-botlens' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>
</div>

<!-- Logs Table Card -->
<div class="mbl-table-card">
	<table class="mbl-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Bot Name', 'mak8it-botlens' ); ?></th>
				<th><?php esc_html_e( 'Status', 'mak8it-botlens' ); ?></th>
				<th><?php esc_html_e( 'IP Address', 'mak8it-botlens' ); ?></th>
				<th><?php esc_html_e( 'Requested URL', 'mak8it-botlens' ); ?></th>
				<th><?php esc_html_e( 'Time (UTC)', 'mak8it-botlens' ); ?></th>
				<th><?php esc_html_e( 'User Agent', 'mak8it-botlens' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $logs ) ) : ?>
				<?php foreach ( $logs as $log ) : ?>
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
						<td style="color: var(--mbl-text-muted); white-space: nowrap;"><?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $log->timestamp ) ); ?></td>
						<td class="mbl-table-ua" title="<?php echo esc_attr( $log->user_agent ); ?>"><?php echo esc_html( $log->user_agent ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6" style="text-align: center; padding: 40px; color: var(--mbl-text-muted); font-weight: 500;">
						<?php esc_html_e( 'No logs match the selected filters.', 'mak8it-botlens' ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- Pagination Area -->
<?php if ( $total_pages > 1 ) : ?>
	<div class="mbl-pagination">
		<div class="mbl-pagination-info">
			<?php printf(
				/* translators: 1: start offset, 2: end offset, 3: total logs */
				esc_html__( 'Showing %1$d to %2$d of %3$d logs', 'mak8it-botlens' ),
				esc_html( number_format_i18n( $offset + 1 ) ),
				esc_html( number_format_i18n( min( $offset + $per_page, $total_records ) ) ),
				esc_html( number_format_i18n( $total_records ) )
			); ?>
		</div>
		<div class="mbl-pagination-links">
			<?php
			$base_url = add_query_arg( array(
				'bot'        => rawurlencode( $filter_bot ),
				'status'     => rawurlencode( $filter_status ),
				'start_date' => rawurlencode( $start_date ),
				'end_date'   => rawurlencode( $end_date ),
			), admin_url( 'admin.php?page=mak8it-botlens-logs' ) );

			$pages = paginate_links( array(
				'base'      => str_replace( '999999999', '%#%', esc_url( add_query_arg( 'paged', 999999999, $base_url ) ) ),
				'format'    => '',
				'total'     => $total_pages,
				'current'   => $current_page,
				'prev_text' => '&laquo; Prev',
				'next_text' => 'Next &raquo;',
				'type'      => 'array',
			) );

			if ( is_array( $pages ) ) {
				foreach ( $pages as $page ) {
					if ( strpos( $page, 'current' ) !== false ) {
						echo '<span class="mbl-page-btn current">' . esc_html( wp_strip_all_tags( $page ) ) . '</span>';
					} else {
						preg_match( '/href=["\']([^"\']*)["\']/', $page, $matches );
						$href = isset( $matches[1] ) ? $matches[1] : '#';
						echo '<a href="' . esc_url( $href ) . '" class="mbl-page-btn">' . esc_html( wp_strip_all_tags( $page ) ) . '</a>';
					}
				}
			}
			?>
		</div>
	</div>
<?php endif; ?>

<?php require_once MBL_PLUGIN_DIR . 'admin/views/footer.php'; ?>
