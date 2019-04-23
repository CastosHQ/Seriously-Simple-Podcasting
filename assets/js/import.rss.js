jQuery(document).ready(function ($) {
	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if ($('#ssp-external-feed-progress').length > 0) {
		update_progress_bar(0, 'blue');
		ssp_import_external_feed();
	}

	function change_progress_colour(colour) {
		console.log(colour);
		let removeClass = 'blue';
		if ('blue' === colour){
			removeClass = 'green';
		}
		$('#ssp-external-feed-progress').removeClass(removeClass).addClass(colour);
	}

	function update_progress_bar(progress, colour) {
		if ('' === colour){
			colour = 'blue';
		}
		$("#ssp-external-feed-progress").progressbar({
			value: progress
		});
		change_progress_colour(colour);
	}

	function update_progress_log(episodes) {
		$('.ssp-ssp-external-feed-message').html('Import completed successfully !').css('color', 'green');
		let ssp_external_feed_status = $('#ssp-external-feed-status');
		let status_html = ssp_external_feed_status.html();
		let log_html = '';
		for (var i = 0; i < episodes.length; i++) {
			log_html = '<p>Imported ' + episodes[i] + '</p>' + log_html;
		}
		status_html = log_html + status_html;
		ssp_external_feed_status.html(status_html);
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed(){
		let timer = setInterval(update_external_feed_progress_bar, 250);
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'import_external_rss_feed'},
		}).done(function (response) {
			clearInterval(timer);
			update_progress_log(response.episodes);
			update_progress_bar(100, 'green');
		}).fail(function (response) {
			console.log(response);
			alert('An error occurred importing the RSS feed, please refresh this page to try again');
		});
	}

	function update_external_feed_progress_bar(){
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'get_external_rss_feed_progress'},
		}).done(function (response) {
			update_progress_bar(response, 'blue');
		});
	}
});
