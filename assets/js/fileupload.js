/**
 * jQuery file upload for SSP/S3 integration
 * Created by Jonathan Bossenger on 2017/01/20.
 */

// Add a listener for a response
window.addEventListener('message', function(evt) {
	console.log(evt);
	// IMPORTANT: Check the origin of the data!
	if (event.origin.indexOf('http://podcastmotor.app') == 0) {
		// Check the response
		console.log(evt.data);
		var result = evt.data;
		if ('' !== result.audio_file){
			console.log(result.audio_file);
			document.getElementById('upload_audio_file').value = result.audio_file;
			tb_remove();
		}
	}
});

jQuery(document).ready(function($) {

	$('#tb_button').on('click', function() {
		// get all of these from WP localize_script
		var upload_url = 'http://podcastmotor.app/upload/';
		var origin = 'http%3A%2F%2Fjonspodcast';
		var api_token = '2y10m12uYMP1nnd9OrwZXndeS9BI05GSKxJp75Itq2faYnOgERE3kE6';

		console.log( upload_url + '?origin=' + origin + '&api_token=' + api_token + '&TB_iframe=true&width=800&height=600' );

		var thickbox = tb_show( 'Seriously Simple Hosting', upload_url + '?origin=' + origin + '&api_token=' + api_token + '&TB_iframe=true&width=800&height=600' );

		// this triggers on window close
		/*
		$('#TB_window').on("tb_unload", function(){
			console.log( $(this) );
			console.log( $('#TB_iframeContent').find('.s3-file-path') );
			alert('Triggered!');
		});
		*/
	});

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
				var notification_message = 'An error occurred, please try again. ' + close_anchor;
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
