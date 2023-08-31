jQuery(document).ready(function ($) {

	/**
	 * Setup the progressbar element
	 * @type {*|jQuery|HTMLElement}
	 */
	var progressbar = $('#ssp-external-feed-progress'),
		$nonce = $('#podcast_settings_tab_nonce'),
		timer,
		isProgressBarActive = true;

	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if (progressbar.length > 0) {
		let progress = progressbar.data('progress'),
			response;
		hide_other_settings();

		response = confirm(
			progress ?
				'Would you like to restore the previous import?' :
				'You are about to import an external RSS feed.'
		);

		if (true === response) {
			ssp_import_external_feed();
			update_progress_bar(progress);
		} else {
			ssp_reset_external_feed();
			show_cancelled_message();
		}
	}

	/**
	 * Change the colour of the progressbar
	 * @param colour
	 */
	function change_progress_colour(colour) {
		var remove_class = 'blue';
		if ('blue' === colour) {
			remove_class = 'green';
		}
		progressbar.removeClass(remove_class).addClass(colour);
	}

	function hide_other_settings(){
		$('.form-table').hide().prev('p').hide();
	}

	/**
	 * Update the progressbar value
	 * @param progress
	 * @param colour
	 */
	function update_progress_bar(progress, colour) {
		if (!colour) {
			colour = 'blue';
		}
		progressbar.progressbar({
			value: progress
		});
		change_progress_colour(colour);
	}

	/**
	 * Update the progress log
	 * @param episodes
	 */
	function update_progress_log(episodes) {
		var ssp_external_feed_status = $('#ssp-external-feed-status');
		var log_html = '';
		for (var i = 0; i < episodes.length; i++) {
			log_html = '<p>Imported ' + episodes[i] + '</p>' + log_html;
		}
		ssp_external_feed_status.html(log_html);
	}

	function show_success_message($msg) {
		$('.ssp-external-feed-message').removeClass('import-error').html($msg);
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed() {
		import_feed();
		handle_progress_bar();
	}

	function handle_progress_bar() {
		isProgressBarActive = true;
		timer = setInterval(update_external_feed_progress_bar, 2000);
	}

	function stop_handling_progress_bar() {
		isProgressBarActive = false;
		clearInterval(timer);
	}

	function import_feed() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				'action': 'import_external_rss_feed',
				'nonce': $nonce.val(),
			},
			timeout: 0,
		}).done(function (response) {
			if ('error' === response['status']) {
				let msg = response.hasOwnProperty('message') ? response.message : '';
				alert_error(msg);
				return;
			}

			// Import 10 items per request.
			if (response['is_finished']) {
				stop_handling_progress_bar();
				update_progress_log(response.episodes);
				update_progress_bar(100, 'green');
				show_success_message(response.message);
				ssp_reset_external_feed();
			} else {
				import_feed();
			}
		}).fail(function (response) {
			let msg = response.hasOwnProperty('message') ? response.message : '';
			alert_error(msg);
		});
	}

	/**
	 * Poll the system to get the progressbar update
	 */
	function update_external_feed_progress_bar() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				'action': 'get_external_rss_feed_progress',
				'nonce': $nonce.val()
			},
		}).done(function (response) {
			if (!isProgressBarActive) {
				return;
			}
			update_progress_bar(response.progress, 'blue');
			update_progress_log(response.episodes);
		});
	}

	/**
	 * Resets the external RSS feed
	 */
	function ssp_reset_external_feed() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				'action': 'reset_rss_feed_data',
				'nonce': $nonce.val()
			},
		}).done(function (response) {
			if ('error' === response['status']) {
				alert('Could not reset current feed import, please refresh this page to try again');
			}
		});
	}

	function show_cancelled_message() {
		$('.ssp-external-feed-message').addClass('import-error').html('<h3>Import cancelled!</h3>');
	}

	/**
	 * Shows an error to user
	 */
	function alert_error($msg = '') {
		$msg = $msg ? $msg : "An error occurred importing the RSS feed. \n\n We'll try to proceed importing after the page refresh.";

		if (!alert($msg)) {
			window.location.reload();
		}
	}
});
