/**
 * Plupload implementation for Seriously Simple Hosting integration
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery( document ).ready( function ( $ ) {

	// upload notification bar
	function notificationBar( message ) {
		$( '.peek-a-bar' ).hide().remove();
		var notification_bar = new $.peekABar( {
			padding: '1em',
			animation: {
				type: 'fade',
				duration: 1000,
			},
			cssClass: 'ssp-notification-bar',
			backgroundColor: '#4aa3df'
		} );
		notification_bar.show( {
			html: message
		} );
	}

	/**
	 * Checks if a file type is valid audio or video
	 * @param file
	 * @returns {boolean}
	 */
	function isFileAllowed( file ) {
		var fileType = file.type;
		var fileTypeParts = fileType.split( "/" );
		var isValid = false;
		if ( 'audio' == fileTypeParts[ 0 ] || 'video' == fileTypeParts[ 0 ] ) {
			isValid = true;
		}
		return isValid;
	}

	/**
	 * Of the upload_credentials object isn't available
	 */
	if ( typeof upload_credentials != "undefined" ) {

		var bucket = upload_credentials.bucket;
		var show_slug = upload_credentials.show_slug;
		var access_key_id = upload_credentials.access_key_id;
		var policy = upload_credentials.policy;
		var signature = upload_credentials.signature;

		/**
		 * Creates instance of plupload
		 * @type {module:plupload.Uploader}
		 */
		var uploader = new plupload.Uploader( {
			runtimes: 'html5',
			browse_button: 'ssp_select_file',
			multi_selection: false,
			container: 'ssp_upload_container',
			url: 'https://' + bucket + '.s3.amazonaws.com:443/',
			multipart_params: {
				'key': show_slug + '/${filename}',
				'Filename': show_slug + '/${filename}',
				'acl': 'public-read',
				'Content-Type': '',
				'AWSAccessKeyId': access_key_id,
				'policy': policy,
				'signature': signature
			}
		} );

		/**
		 * Init Uploader
		 */
		uploader.init();

		/**
		 * Remove html5 not supported message if uploader inits successfully
		 */
		uploader.bind( 'Init', function () {
			$( '#ssp_upload_notification' ).remove();
		} );

		/**
		 * Checks for a valid file type triggers upload if successful
		 */
		uploader.bind( 'FilesAdded', function ( up, files ) {
			console.log( files );
			// we've turned off multi file select so we're only expecting one file
			var file = files[ 0 ];
			if ( isFileAllowed( file ) ) {
				notificationBar( 'Uploading file to Seriously Simple Hosting. You can continue editing this post while the file uploads.<b id="ssp_upload_progress"></b>' );
				uploader.start();
			} else {
				notificationBar( 'You have selected an invalid file type, please select a valid audio or video file.' );
			}
		} );

		/**
		 * Show an error if anything goes wrong
		 */
		uploader.bind( 'Error', function ( up, err ) {
			alert( 'Error #' + err.code + ': ' + err.message );
		} );

		/**
		 * Update the notification bar on upload progress
		 */
		uploader.bind( 'UploadProgress', function ( up, file ) {
			$( '#ssp_upload_progress' ).html( file.percent + '%' );
		} );

		/**
		 * Return the file upload and display a complete message on complete
		 */
		uploader.bind( 'UploadComplete', function ( up, files ) {
			//'https://episodes.seriouslysimplepodcasting.com/jons-podcast-on-staging/'
			notificationBar( 'Uploading file to Seriously Simple Hosting Complete.' );
			$( '#upload_audio_file' ).val( 'https://s3.amazonaws.com/seriouslysimplestaging/' + bucket + '/' + files[ 0 ].name );
			$( '.peek-a-bar' ).fadeOut( 5000 );
		} );

	}

} );

/**
 // Add a listener for a response
 window.addEventListener('message', function(evt) {
		console.log(evt);
		// IMPORTANT: Check the origin of the data!
		var origin_url_to_check = sshObject.ssh_url;
		origin_url_to_check = origin_url_to_check.slice(0, -1);
		//console.log( origin_url_to_check );
		if (event.origin.indexOf(origin_url_to_check) == 0) {
			// Check the response
			//console.log(evt.data);
			var result = evt.data;
			if ('' !== result.audio_file){
				//console.log(result.audio_file);
				document.getElementById('upload_audio_file').value = result.audio_file;
				tb_remove();
			}
		}
	});
 */

/**
 * Old file uploader

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
 */