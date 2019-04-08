jQuery(document).ready(function ($) {

	/**
	 * If the progress bar appears on the page, trigger the import
	 */
	if ($('#ssp-external-feed-progress').length > 0) {
		$("#ssp-external-feed-progress").progressbar({
			value: 1
		});
		ssp_import_external_feed();
	}

	/**
	 * Import the external RSS feed
	 */
	function ssp_import_external_feed(){
		$.ajax({
			url: path,
			type: 'post',
			data: {payload: payload},
			xhr: function () {
				var xhr = $.ajaxSettings.xhr();
				xhr.onprogress = function e() {
					// For downloads
					if (e.lengthComputable) {
						console.log(e.loaded / e.total);
					}
				};
				xhr.upload.onprogress = function (e) {
					// For uploads
					if (e.lengthComputable) {
						console.log(e.loaded / e.total);
					}
				};
				return xhr;
			}
		}).done(function (e) {
			// Do something
		}).fail(function (e) {
			// Do something
		});
	}
});
