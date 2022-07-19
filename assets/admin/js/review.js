jQuery(document).ready(function ($) {
	let $notice = $('.js-ssp-review-notice');

	if (!$notice.length) {
		return;
	}

	function hide_notice() {
		$notice.fadeTo(100, 0, function () {
			$notice.slideUp(100, function () {
				$notice.remove();
			});
		});
	}

	function send_request(data) {
		$.post(ajaxurl, data);
	}

	$notice.find('.js-ssp-change-review-status').on('click', function () {
		send_request({
			'action': 'ssp_review_notice_status',
			'status': $(this).data('status'),
			'nonce': $(this).data('nonce'),
		});
		hide_notice();
	});
});
