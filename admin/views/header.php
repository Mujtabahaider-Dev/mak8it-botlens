<?php
/**
 * Admin Header Template for Mak8it BotLens.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET request for identifying active tab.
$mbl_current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
?>

<div class="mbl-admin-wrap">
	<!-- Page Header Card -->
	<header class="mbl-header-card">
		<!-- Left: Branding -->
		<div class="mbl-branding">
			<div class="mbl-logo-box">
				<span class="dashicons dashicons-shield"></span>
			</div>
			<div class="mbl-title-group">
				<h1><?php esc_html_e( 'Mak8it BotLens', 'mak8it-botlens' ); ?></h1>
			</div>
		</div>

		<!-- Right: Navigation Tabs -->
		<nav class="mbl-nav-tabs">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens' ) ); ?>"
				class="mbl-nav-link <?php echo 'mak8it-botlens' === $mbl_current_page ? 'active' : ''; ?>">
				<?php esc_html_e( 'Dashboard', 'mak8it-botlens' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-logs' ) ); ?>"
				class="mbl-nav-link <?php echo 'mak8it-botlens-logs' === $mbl_current_page ? 'active' : ''; ?>">
				<?php esc_html_e( 'Logs', 'mak8it-botlens' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mak8it-botlens-settings' ) ); ?>"
				class="mbl-nav-link <?php echo 'mak8it-botlens-settings' === $mbl_current_page ? 'active' : ''; ?>">
				<?php esc_html_e( 'Settings', 'mak8it-botlens' ); ?>
			</a>
		</nav>
	</header>
