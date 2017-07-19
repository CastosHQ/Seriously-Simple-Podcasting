/**
 * Plupload implementation for Seriously Simple Hosting integration
 * Created by Jonathan Bossenger on 2017/01/20.
 */

// console.log(sshObject);

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
	 * Creates instance of plupload
	 * @type {module:plupload.Uploader}
	 */
	var uploader = new plupload.Uploader( {
		runtimes: 'html5',
		browse_button: 'ssp_select_file',
		multi_selection: false,
		container: 'ssp_upload_container',
		url: 'https://' + upload_credentials.bucket + '.s3.amazonaws.com:443/',
		multipart_params: {
			'key': 'jons-podcast-on-staging/${filename}', // use filename as a key
			'Filename': 'jons-podcast-on-staging/${filename}', // adding this to keep consistency across the runtimes
			'acl': 'public-read',
			'Content-Type': '',
			'AWSAccessKeyId': upload_credentials.access_key_id,
			'policy': upload_credentials.policy,
			'signature': upload_credentials.signature
		}
	} );

	// Init ////////////////////////////////////////////////////
	uploader.init();

	uploader.bind( 'Init', function ( up, params ) {
		$( '#ssp_upload_notification' ).remove();
		//document.getElementById('filelist').innerHTML = '';
	} );

	/**
	uploader.bind('PostInit', function() {
		document.getElementById('uploadfiles').onclick = function() {
			uploader.start();
			return false;
		};
	});
	*/

	// Selected Files
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

		/**
		plupload.each( files, function ( file ) {
			//document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
			if ( isFileAllowed( file ) ) {
				notificationBar( 'Uploading file to Seriously Simple Hosting. You can continue editing this post while the file uploads.<b id="ssp_upload_progress"></b>' );
				//uploader.start();
			}
			var fileType = file.type;
			var fileTypeParts = fileType.split( "/" );
			console.log( fileTypeParts );
		} );
		*/
		return false;
	} );

	// Error Alert
	uploader.bind( 'Error', function ( up, err ) {
		alert( 'Error #' + err.code + ': ' + err.message );
	} );

	// Progress bar
	uploader.bind( 'UploadProgress', function ( up, file ) {
		//notificationBar('Uploading file to Seriously Simple Hosting. You can continue editing this post while the file uploads. <b>' + file.percent + '%</b>');
		//document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + '%</span>';
		$( '#ssp_upload_progress' ).html( file.percent + '%' );
	} );

	// Upload Complete
	uploader.bind( 'UploadComplete', function ( up, files ) {
		//'https://episodes.seriouslysimplepodcasting.com/jons-podcast-on-staging/'
		notificationBar( 'Uploading file to Seriously Simple Hosting Complete.' );
		$( '#upload_audio_file' ).val( 'https://s3.amazonaws.com/seriouslysimplestaging/jons-podcast-on-staging/' + files[ 0 ].name );
		$( '.peek-a-bar' ).fadeOut( 5000 );
	} );

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