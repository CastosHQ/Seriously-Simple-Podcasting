jQuery(document).ready(function ($) {

	/**
	 * Setup the progressbar element
	 * @type {*|jQuery|HTMLElement}
	 */
	var progressbar = $('#ssp-external-feed-progress');

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
		$('.ssp-ssp-external-feed-message').html('Import completed successfully !').css('color', 'green');
		var ssp_external_feed_status = $('#ssp-external-feed-status');
		var status_html = ssp_external_feed_status.html();
		var log_html = '';
		for (var i = 0; i < episodes.length; i++) {
			log_html = '<p>Imported ' + episodes[i] + '</p>' + log_html;
		}
		status_html = log_html + status_html;
		ssp_external_feed_status.html(status_html);
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed() {
		var timer = setInterval(update_external_feed_progress_bar, 250);
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'import_external_rss_feed'},
		}).done(function (response) {
			clearInterval(timer);
			update_progress_log(response.episodes);
			update_progress_bar(100, 'green');
		}).fail(function (response) {
			alert('An error occurred importing the RSS feed, please refresh this page to try again');
		});
	}

	/**
	 * Poll the system to get the progressbar update
	 */
	function update_external_feed_progress_bar() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'get_external_rss_feed_progress'},
		}).done(function (response) {
			update_progress_bar(response, 'blue');
		});
	}

	/**
	 * Resets the external RSS feed
	 */
	function ssp_reset_external_feed() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'reset_external_rss_feed_progress'},
		}).done(function () {
			$('.ssp-ssp-external-feed-message').html('Import cancelled !').css('color', 'red');
			$('#ssp-external-feed-status').html('');
		});
	}
});
