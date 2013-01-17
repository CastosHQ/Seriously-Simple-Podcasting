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
		tb_show( 'Upload an audio file' , 'media-upload.php?referer=ss_podcast_image&type=image&TB_iframe=true' , false );
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

});