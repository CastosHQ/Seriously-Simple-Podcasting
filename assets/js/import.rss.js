jQuery(document).ready(function ($) {

	/**
	 * Setup the progressbar element
	 * @type {*|jQuery|HTMLElement}
	 */
	var progressbar = $('#ssp-external-feed-progress'),
		$nonce = $('#podcast_settings_tab_nonce'),
		timer;

	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if (progressbar.length > 0) {
		var response = confirm('You are about to import an external RSS feed.');
		if (true === response) {
			update_progress_bar(0);
			ssp_import_external_feed();
		} else {
			ssp_reset_external_feed();
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

	/**
	 * Update the progressbar value
	 * @param progress
	 * @param colour
	 */
	function update_progress_bar(progress, colour) {
		/**
		 * First run
		 */
		if (0 === progress) {
			progressbar.progressbar({
				value: 0
			});
			return;
		}

		/**
		 * Subsequent runs
		 */
		if ('' === colour) {
			colour = 'blue';
		}
		var current_value = progressbar.progressbar('value');
		if (current_value < 100) {
			progressbar.progressbar({
				value: progress
			});
			change_progress_colour(colour);
		}
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

	function show_success_message() {
		$('.ssp-ssp-external-feed-message').html('Import completed successfully !').css('color', 'green');
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed() {
		handle_progress_bar();
		import_feed(true);
	}

	function handle_progress_bar() {
		timer = setInterval(update_external_feed_progress_bar, 2000);
	}

	function stop_handling_progress_bar() {
		clearInterval(timer);
	}

	function import_feed(isInitial) {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				'action': 'import_external_rss_feed',
				'nonce': $nonce.val(),
				'isInitial': isInitial,
			},
			timeout: 0,
		}).done(function (response) {
			if ('error' === response['status']) {
				let msg = response.hasOwnProperty('message') ? response.message : '',
					tryAgain = response.hasOwnProperty('can_try_again') ? response.can_try_again : true;
				alert_error(msg, tryAgain);
				return;
			}

			// Import 10 items per request.
			if (response['is_finished']) {
				stop_handling_progress_bar();
				update_progress_log(response.episodes);
				update_progress_bar(100, 'green');
				show_success_message();
			} else {
				import_feed(false);
			}
		}).fail(function (response) {
			let msg = response.hasOwnProperty('message') ? response.message : '',
				tryAgain = response.hasOwnProperty('can_try_again') ? response.can_try_again : true;
			alert_error(msg, tryAgain);
		});
	}

	function reset_import_data() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {
				'action': 'ssp_reset_import_data',
				'nonce': $nonce.val(),
			},
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
				'action': 'reset_external_rss_feed_progress',
				'nonce': $nonce.val()
			},
		}).done(function (response) {
			if ('error' === response['status']) {
				alert('Could not reset current feed import, please refresh this page to try again');
				return;
			}

			$('.ssp-ssp-external-feed-message').html('Import cancelled !').css('color', 'red');
		});
	}

	/**
	 * Shows an error to user
	 */
	function alert_error($msg = '', tryAgain = false) {
		$msg = $msg ? $msg : 'An error occurred importing the RSS feed. Would you like to proceed importing?';

		if (tryAgain) {
			return maybe_try_again($msg);
		}

		stop_handling_progress_bar();

		// If not startFrom specified, we do not allow to try it again
		if (!alert($msg)) {
			reset_import_data();
			window.location.reload();
		}
	}

	function maybe_try_again($msg) {
		if (confirm($msg)) {
			import_feed(false);
		} else {
			stop_handling_progress_bar();
			ssp_reset_external_feed();
		}
	}
});
