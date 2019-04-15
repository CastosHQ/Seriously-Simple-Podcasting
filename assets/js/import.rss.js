jQuery(document).ready(function ($) {

	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if ($('#ssp-external-feed-progress').length > 0) {
		$("#ssp-external-feed-progress").progressbar({
			value: 0
		});
		ssp_import_external_feed();
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed(){
		$.ajax({
			url: ajaxurl,
			data: {'action': 'import_external_rss_feed'},
		}).progress(function (e) {
			console.log(['Progress', e]);
		}).uploadProgress(function (e) {
			console.log(['Progress Upload', e]);
		}).done(function (e) {
			console.log(['Done', e]);
		}).fail(function (e) {
			console.log(['Fail', e]);
		});
	}
});
