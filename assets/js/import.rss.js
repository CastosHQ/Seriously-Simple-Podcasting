jQuery(document).ready(function ($) {
	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if ($('#ssp-external-feed-progress').length > 0) {

		var response = confirm('You are about to import an external RSS feed.');
		if (response == true) {
			$("#ssp-external-feed-progress").progressbar({
				value: 0
			});
			ssp_import_external_feed();
		} else {
			ssp_reset_external_feed();
		}


	}

	function update_progress_bar(progress) {
		$("#ssp-external-feed-progress").progressbar({
			value: progress
		});
	}

	function update_progress_log(episodes) {
		let ssp_external_feed_status = $('#ssp-external-feed-status');
		let status_html = ssp_external_feed_status.html();
		let log_html = '';
		for (var i = 0; i < episodes.length; i++) {
			log_html = '<p>Imported' + episodes[i] + '</p>' + log_html;
		}
		status_html = '<p><strong>Import complete</strong></p>' + log_html + status_html;
		ssp_external_feed_status.html(status_html);
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed() {
		let timer = setInterval(update_external_feed_progress_bar, 500);
		update_progress_bar(10);
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'import_external_rss_feed'},
		}).done(function (response) {
			clearInterval(timer);
			update_progress_log(response.episodes);
			update_progress_bar(100);
		}).fail(function (response) {
			alert('An error occurred importing the RSS feed, please refresh this page to try again');
		});
	}

	function update_external_feed_progress_bar() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'get_external_rss_feed_progress'},
		}).done(function (response) {
			update_progress_bar(response);
		});
	}

	function ssp_reset_external_feed() {
		// reset back to importer
	}
});
