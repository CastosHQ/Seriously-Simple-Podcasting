jQuery(document).ready(function($) {

	/* ADD/EDIT EPISODE */

	// Uploading files
	var file_frame;

	jQuery.fn.ssp_upload_media_file = function( button, preview_media ) {
		var button_id = button.attr('id');
		var field_id = button_id.replace( '_button', '' );
		var preview_id = button_id.replace( '_button', '_preview' );

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
		  file_frame.open();
		  return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
		  title: jQuery( this ).data( 'uploader_title' ),
		  button: {
		    text: jQuery( this ).data( 'uploader_button_text' ),
		  },
		  multiple: false
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
		  attachment = file_frame.state().get('selection').first().toJSON();
		  jQuery("#"+field_id).val(attachment.url);
		  if( preview_media ) {
		  	jQuery("#"+preview_id).attr('src',attachment.url);
		  }
		});

		// Finally, open the modal
		file_frame.open();
	}

	jQuery('#upload_audio_file_button').click(function( event ){
		event.preventDefault();
		jQuery.fn.ssp_upload_media_file( jQuery(this), false );
	});

	/* SETTINGS PAGE */

	jQuery('#ss_podcasting_data_image_button').click(function() {
		jQuery.fn.ssp_upload_media_file( jQuery(this), true );
	});

	jQuery('#ss_podcasting_delete_image').click(function() {
		jQuery( '#ss_podcasting_data_image' ).val( '' );
		jQuery( '#ss_podcasting_data_image_preview' ).remove();
		return false;
	});

	// Make sure each heading has a unique ID.
	jQuery( 'ul#settings-sections.subsubsub' ).find( 'a' ).each( function ( i ) {
		var id_value = jQuery( this ).attr( 'href' ).replace( '#', '' );
		jQuery( 'h3:contains("' + jQuery( this ).text() + '")' ).attr( 'id', id_value ).addClass( 'section-heading' );
	});

	// Create nav links for settings page
	jQuery( '#podcast_settings .subsubsub a.tab' ).click( function ( e ) {
		// Move the "current" CSS class.
		jQuery( this ).parents( '.subsubsub' ).find( '.current' ).removeClass( 'current' );
		jQuery( this ).addClass( 'current' );

		// If "All" is clicked, show all.
		if ( jQuery( this ).hasClass( 'all' ) ) {
			jQuery( '#podcast_settings h3, #podcast_settings form p, #podcast_settings table.form-table, p.submit' ).show();

			return false;
		}

		// If the link is a tab, show only the specified tab.
		var toShow = jQuery( this ).attr( 'href' );

		// Remove the first occurance of # from the selected string (will be added manually below).
		toShow = toShow.replace( '#', '', toShow );

		jQuery( '#podcast_settings h3, #podcast_settings form > p:not(".submit"), #podcast_settings table' ).hide();
		jQuery( 'h3#' + toShow ).show().nextUntil( 'h3.section-heading', 'p, table, table p' ).show();

		return false;
	});

});