<?php
/**
 * Admin Footer Template for Mak8it BotLens.
 *
 * @package Mak8it_BotLens
 */

defined( 'ABSPATH' ) || exit;
?>

	<footer style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--mbl-border); display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--mbl-text-muted); font-weight: 500;">
		<p>
			<?php
			/* translators: %s: Current year */
			printf( esc_html__( '© %s Mak8it BotLens. All rights reserved.', 'mak8it-botlens' ), esc_html( gmdate( 'Y' ) ) );
			?>
		</p>
		<p>
			<?php esc_html_e( 'Made with', 'mak8it-botlens' ); ?> <span style="color: var(--mbl-danger);">♥</span>
			<?php esc_html_e( 'by', 'mak8it-botlens' ); ?>
			<a href="https://mak8it.com" target="_blank" rel="noopener noreferrer" style="color: var(--mbl-primary); font-weight: 700; text-decoration: none;">Mak8it.com</a>
		</p>
	</footer>
</div>
