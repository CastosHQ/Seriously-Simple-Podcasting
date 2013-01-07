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

});