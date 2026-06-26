/**
 * jQuery UI Controller for Mak8it BotLens.
 * Handles AJAX, settings forms, filters, onboarding banners, and toasts.
 */

(function ($) {
	"use strict";

	$(document).ready(function () {
		initSettingsSave();
		initClearLogs();
		initOnboardingModal();
		initFilters();
	});

	/**
	 * Display toast notification matching the Smart Image aesthetic
	 */
	function showToast(msg, type) {
		$('.mbl-toast-js').remove();

		const bgColorClass = type === 'success' ? 'mbl-toast-success' : 'mbl-toast-error';
		const icon = type === 'success' ? 'check_circle' : 'error';

		const $toast = $(`
			<div class="mbl-toast-js ${bgColorClass} translate-y-20 opacity-0">
				<span class="mbl-material-icon">${icon}</span>
				<span class="mbl-toast-message">${msg}</span>
			</div>
		`);

		$('body').append($toast);

		setTimeout(() => {
			$toast.removeClass('translate-y-20 opacity-0');
		}, 10);

		const timer = setTimeout(() => {
			$toast.addClass('translate-y-20 opacity-0');
			setTimeout(() => $toast.remove(), 500);
		}, 3000);

		$toast.on('click', function () {
			clearTimeout(timer);
			$toast.addClass('translate-y-20 opacity-0');
			setTimeout(() => $toast.remove(), 500);
		});
	}

	/**
	 * Handle settings saving via AJAX
	 */
	function initSettingsSave() {
		$('#mbl-settings-form').on('submit', function (e) {
			e.preventDefault();

			const $form = $(this);
			const $btn = $('#mbl-save-settings-btn');
			const originalText = $btn.text();

			$btn.prop('disabled', true).css('opacity', '0.7').text('Saving...');

			// Serialize form data
			const formData = $form.serialize();

			$.ajax({
				url: mblAdmin.ajax_url,
				type: 'POST',
				data: formData + '&action=mbl_save_settings&nonce=' + mblAdmin.settings_nonce,
				success: function (response) {
					if (response.success) {
						showToast('✓ ' + (response.data.message || 'Settings saved successfully.'), 'success');
					} else {
						showToast('✗ ' + (response.data.message || 'An error occurred.'), 'error');
					}
				},
				error: function () {
					showToast('✗ Connection error', 'error');
				},
				complete: function () {
					$btn.prop('disabled', false).css('opacity', '1').text(originalText);
				}
			});
		});
	}

	/**
	 * Handle clearing crawler logs via AJAX
	 */
	function initClearLogs() {
		$('#mbl-clear-logs-btn').on('click', function () {
			if (!confirm('Are you sure you want to permanently clear all crawler logs? This action cannot be undone.')) {
				return;
			}

			const $btn = $(this);
			const originalText = $btn.text();

			$btn.prop('disabled', true).css('opacity', '0.7').text('Clearing...');

			$.ajax({
				url: mblAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'mbl_clear_logs',
					nonce: mblAdmin.clear_nonce
				},
				success: function (response) {
					if (response.success) {
						showToast('✓ ' + (response.data.message || 'Logs cleared successfully.'), 'success');
						setTimeout(() => {
							location.reload();
						}, 1000);
					} else {
						showToast('✗ ' + (response.data.message || 'Failed to clear logs.'), 'error');
						$btn.prop('disabled', false).css('opacity', '1').text(originalText);
					}
				},
				error: function () {
					showToast('✗ Connection error', 'error');
					$btn.prop('disabled', false).css('opacity', '1').text(originalText);
				}
			});
		});
	}

	/**
	 * Dismiss onboarding modal and save to localStorage
	 */
	function initOnboardingModal() {
		const $modal = $('#mbl-onboarding-modal');
		if (!$modal.length) {
			return;
		}

		if (localStorage.getItem('mbl_modal_dismissed') === '1') {
			$modal.remove();
		}

		function dismissModal() {
			$modal.addClass('mbl-modal-dismissed');
			setTimeout(() => {
				$modal.remove();
			}, 300);
			localStorage.setItem('mbl_modal_dismissed', '1');
		}

		$('#mbl-modal-close-btn, #mbl-modal-skip-btn').on('click', function () {
			dismissModal();
		});

		$modal.on('click', function (e) {
			if ($(e.target).is('#mbl-onboarding-modal')) {
				dismissModal();
			}
		});
	}

	/**
	 * Automatically submit logs filter forms on dropdown change
	 */
	function initFilters() {
		const $form = $('#mbl-logs-filter-form');
		if (!$form.length) {
			return;
		}

		// Auto-submit dropdowns
		$form.find('select').on('change', function () {
			$form.submit();
		});
	}

})(jQuery);
