/**
 * jQuery file upload for SSP/S3 integration
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery(document).ready(function($) {

	function notificationBar( message ){
		$('.peek-a-bar').hide().remove();
		var notification_bar = new $.peekABar({
			padding: '1em',
			animation: {
				type: 'fade',
				duration: 1000,
			},
			cssClass: 'ssp-notification-bar',
			backgroundColor: '#4aa3df'
		});
		notification_bar.show({
			html: message
		});
	}

	$('#fileupload').fileupload({
		url: ajaxurl,
		formData: {
			action: 'ssp_upload_to_podmotor'
		},
		dataType: 'json',
		start: function ( ) {
			notificationBar('Uploading file to Seriously Simple Hosting. You can continue editing this post while the file uploads.');
		},
		done: function (e, data) {
			var result = data.result;
			var close_anchor = ' <a class=\'close-ssp-notification\'>x</a>';
			if ( result.status === 'success' ){
				notificationBar('Uploading file to Seriously Simple Hosting Complete.' + close_anchor);
				$('#upload_audio_file').val( result.file_path );
				$('#podmotor_file_id').val( result.file_id );
				$('#duration').val( result.duration );
				$('#upload_audio_file').siblings('span.description').html('Your media file has been successfully uploaded.');
				$('.peek-a-bar').fadeOut(5000);
			}else {
				var notification_message = 'An error occurred, please try again.' + close_anchor;
				if ( result.message !== ''){
					notification_message = result.message + close_anchor;
				}
				notificationBar(notification_message);
			}
		}
	});

	$( 'body' ).on( 'click', 'a.close-ssp-notification', function() {
		$('.peek-a-bar').hide().remove();
	});

});
