/**
 * Plupload implementation for Castos Hosting integration
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery( document ).ready( function ( $ ) {
	/**
	 * Upload notification bar
	 */
	function notificationBar( message ) {
		$( '.peek-a-bar' ).hide().remove();
		var notification_bar = new $.peekABar(
			{
				padding: '1em',
				animation: {
					type: 'fade',
					duration: 1000,
				},
				cssClass: 'ssp-notification-bar',
				backgroundColor: '#4aa3df'
			}
		);
		notification_bar.show(
			{
				html: message
			}
		);
	}

	/**
	 * Checks if a file type is valid audio or video
	 * @param file
	 * @returns {boolean}
	 */
	function isFileAllowed( file ) {
		var fileType      = file.type;
		var fileTypeParts = fileType.split( "/" );
		var isValid       = false;
		if ( 'audio' === fileTypeParts[ 0 ] || 'video' === fileTypeParts[ 0 ] ) {
			isValid = true;
		}
		return isValid;
	}

	/**
	 * Sanitize the file name
	 * @param name
	 * @returns {string}
	 */
	function sanitizeName(name) {
		var punctuationlessName = name.replace( /[,\/#!$%\^&\*;:{}=\-_`'~()+"|? ]/g," " );
		return punctuationlessName.replace( /\s{1,}/g,"-" );
	}

	/**
	 * If the upload_credentials object isn't available
	 */
	if ( typeof upload_credentials != "undefined" ) {
		/**
		 * Creates instance of plupload
		 * @type {module:plupload.Uploader}
		 */
		var uploader = new plupload.Uploader(
			{
				runtimes: 'html5',
				browse_button: 'ssp_select_file',
				multi_selection: false,
				container: 'ssp_upload_container',
				url: upload_credentials.castos_api_url + 'files',
			}
		);

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
		uploader.bind('FilesAdded', function (up, files) {
			// we've turned off multi file select so we're only expecting one file
			var file = files[0];
			if (isFileAllowed(file)) {
				notificationBar('Uploading file to Castos Hosting. You can continue editing this post while the file uploads. <b id="ssp_upload_progress"></b>');
				uploader.start();
			} else {
				notificationBar('You have selected an invalid file type, please select a valid audio or video file.');
				uploader.removeFile(file);
			}
		});

		/**
		 * Sanatizes the file name for upload
		 */
		uploader.bind('BeforeUpload', function (up, file) {
			var file_name = sanitizeName(file.name);
			var multipart_params = {
				'token': upload_credentials.castos_api_token,
				'episode_id': upload_credentials.castos_episode_id,
				'file_name': file_name
			};
			uploader.settings.multipart_params = multipart_params;
		});

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
			if (file.percent === 100) {
				notificationBar( 'Processing Castos Hosting file.' );
			}
		});

		/**
		 * Update the notification bar on upload progress
		 */
		uploader.bind( 'FileUploaded', function ( up, file, result ) {
			notificationBar( 'Uploading file to Castos Hosting Complete.' );
			var response = JSON.parse(result.response);
			if ( response.status === 200 ) {
				var file = response.file;
				/**
				 * @todo sanitize file name ???
				 */
				$( "#podmotor_file_id" ).val( file.id );
				$( "#filesize_raw" ).val( file.file_size );
				$( "#filesize" ).val( plupload.formatSize( file.file_size ) );
				$( "#duration" ).val( file.file_duration );
				$( '#upload_audio_file' ).val( file.file_path );
				$( '.peek-a-bar' ).fadeOut( 5000 );
			}
		} );

		/**
		 * Hide the notification bar once the upload is finished
		 */
		uploader.bind( 'UploadComplete', function ( up, files ) {
			$( '.peek-a-bar' ).fadeOut( 5000 );
		} );

	}
});
