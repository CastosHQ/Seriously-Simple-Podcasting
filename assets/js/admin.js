jQuery(document).ready(function($) {

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

		console.log( file_frame );

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
		  var attachment = file_frame.state().get('selection').first().toJSON();

		  console.log(attachment);

		  jQuery("#"+field_id).val(attachment.url);
		  if ( preview_media ) {
		  	jQuery("#"+preview_id).attr('src',attachment.url);
		  }
		});

		// Finally, open the modal
		file_frame.open();
	};

	/* ADD/EDIT EPISODE */

	jQuery('#upload_audio_file_button').click(function( event ){
		event.preventDefault();
		jQuery.fn.ssp_upload_media_file( jQuery(this), false );
	});

	jQuery('#episode_embed_code').click(function() {
		jQuery(this).select();
	});

	jQuery( '.episode_embed_code_size_option' ).change(function() {

		var width = jQuery( '#episode_embed_code_width' ).val();
		var height = jQuery( '#episode_embed_code_height' ).val();
		var post_id = jQuery( '#post_ID' ).val();

		jQuery.post(
		    ajaxurl,
		    {
		        'action': 'update_episode_embed_code',
		        'width': width,
		        'height': height,
		        'post_id': post_id,
		    },
		    function( response ){
		        if( response ) {
		        	jQuery( '#episode_embed_code' ).val( response );
		        	jQuery( '#episode_embed_code' ).select();
		        }
		    }
		);
	});

	/* DATEPICKER */

	jQuery('.ssp-datepicker').datepicker({
		changeMonth: true,
      	changeYear: true,
      	showAnim: 'slideDown',
      	dateFormat: 'd MM, yy',
      	altField: '#date_recorded',
      	altFormat: 'dd-mm-yy',
      	onClose : function ( dateText, obj ) {
		    var d = $.datepicker.parseDate("d MM, yy", dateText);
		    var date = $.datepicker.formatDate("dd-mm-yy", d);
		    var save_field = $(this).attr('id').replace( '_display', '' );
		    $( '#' + save_field ).val( date );
		}
	});

	jQuery('.ssp-datepicker').change( function () {
		var value = jQuery( this ).val();
		if( !value ) {
			var id = jQuery( this ).attr( 'id' );
			var save_field = id.replace( '_display', '' );
			jQuery( '#' + save_field ).val( '' );
		}
	});

	/* SETTINGS PAGE */

	jQuery('#feed-series-toggle').click(function(e) {

		if ( jQuery(this).hasClass( 'series-open' ) ) {
			jQuery('#feed-series-list').slideUp('fast');
			jQuery(this).removeClass( 'series-open' );
			jQuery(this).addClass( 'series-closed' );

		} else if ( jQuery(this).hasClass( 'series-closed' ) ) {
			jQuery('#feed-series-list').slideDown('fast');
			jQuery(this).removeClass( 'series-closed' );
			jQuery(this).addClass( 'series-open' );

		}

	});

	jQuery('#ss_podcasting_data_image_button').click(function() {
		jQuery.fn.ssp_upload_media_file( jQuery(this), true );
	});

	jQuery('#ss_podcasting_data_image_delete').click(function() {
		jQuery( '#ss_podcasting_data_image' ).val( '' );
		jQuery( '#ss_podcasting_data_image_preview' ).remove();
		return false;
	});

});