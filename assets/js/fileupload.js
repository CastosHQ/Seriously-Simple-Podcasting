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
		var episodes_url = upload_credentials.episodes_url;
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
			// we've turned off multi file select so we're only expecting one file
			var file = files[ 0 ];
			if ( isFileAllowed( file ) ) {
				notificationBar( 'Uploading file to Seriously Simple Hosting. You can continue editing this post while the file uploads. <b id="ssp_upload_progress"></b>' );
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

			notificationBar( 'Processing Seriously Simple Hosting file.' );

			// we're only expecting one file to be uploaded
			var file = files[ 0 ];
			var filesize_raw = file.size;
			var file_size = plupload.formatSize(file.size);
			var uploaded_file = 'https://s3.amazonaws.com/' + bucket + '/' + show_slug + '/' + file.name;
			var episode_file = episodes_url + show_slug + '/' + file.name;

			// push podmotor_file_path to wp_ajax_ssp_store_podmotor_file
			$.ajax( {
				method: "GET",
				url: ajaxurl,
				data: { action: "ssp_store_podmotor_file", podmotor_file_path: uploaded_file }
			} )
				.done( function ( response ) {
					if ( response.status == 'success' ) {
						notificationBar( 'Uploading file to Seriously Simple Hosting Complete.' );
						$( "#podmotor_file_id" ).val( response.file_id );
						$( "#filesize_raw" ).val( filesize_raw );
						$( "#filesize" ).val( file_size );
						$( "#duration" ).val( response.file_duration );
						$( '#upload_audio_file' ).val( episode_file );
						$( '.peek-a-bar' ).fadeOut( 5000 );
					} else {
						notificationBar( response.message );
					}
				} );
		} );
	}
} );