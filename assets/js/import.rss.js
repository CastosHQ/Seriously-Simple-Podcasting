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
		}else {
			ssp_reset_external_feed();
		}


	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed() {
		let timer = setInterval(update_external_feed_progress_bar, 500);
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'import_external_rss_feed'},
		}).done(function (response) {
			console.log(response);
			console.log('complete');
			clearInterval(timer);
		}).fail(function (response) {
			console.log(response);
			console.log('error');
		});
	}

	function update_external_feed_progress_bar() {
		$.ajax({
			url: ajaxurl,
			type: 'get',
			data: {'action': 'get_external_rss_feed_progress'},
		}).done(function (response) {
			console.log(response);
		}).fail(function (response) {
			console.log(response);
		});
	}

	function ssp_reset_external_feed(){

	}
});
