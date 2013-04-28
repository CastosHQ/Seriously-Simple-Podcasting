jQuery(document).ready(function($) {

	$('#upload_file_button').click(function() {
		var post_id = jQuery( '#seriouslysimple_post_id' ).val();
		tb_show( 'Upload an audio file' , 'media-upload.php?referer=seriouslysimple-file&type=audio&TB_iframe=true&post_id=' + post_id , false );
		return false;
	});

	if( jQuery( '#upload_file_button' ).length > 0 ) {
		window.send_to_editor = function(html) {
			var file_url = jQuery( html ).attr( 'href' );
			jQuery( '#enclosure' ).val( file_url );
			tb_remove();
		}
	}

	$('#ss_podcasting_upload_image').click(function() {
		tb_show( 'Upload an image' , 'media-upload.php?referer=ss_podcast_image&type=image&TB_iframe=true' , false );
		return false;
	});

	if( jQuery( '#ss_podcasting_upload_image' ).length > 0 ) {
		window.send_to_editor = function(html) {
			var file_url = jQuery( 'img' , html ).attr( 'src' );
			jQuery( '#ss_podcasting_data_image' ).val( file_url );
			jQuery( '#ss_podcasting_data_image_preview' ).attr( 'src' , file_url );
			tb_remove();
		}
	}

	$('#ss_podcasting_delete_image').click(function() {
		$( '#ss_podcasting_data_image' ).val( '' );
		$( '#ss_podcasting_data_image_preview' ).remove();
		return false;
	});

	// Make sure each heading has a unique ID.
	jQuery( 'ul#settings-sections.subsubsub' ).find( 'a' ).each( function ( i ) {
		var id_value = jQuery( this ).attr( 'href' ).replace( '#', '' );
		jQuery( 'h3:contains("' + jQuery( this ).text() + '")' ).attr( 'id', id_value ).addClass( 'section-heading' );
	});

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