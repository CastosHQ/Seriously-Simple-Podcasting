jQuery(document).ready(function($) {

	// Uploading files
	var file_frame, series_img_frame;

	$.fn.ssp_upload_media_file = function( button, preview_media, validateImageSize = false ) {
		var button_id = button.attr('id');
		var field_id = button_id.replace( '_button', '' );
		var preview_id = button_id.replace( '_button', '_preview' );

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
		  title: $( this ).data( 'uploader_title' ),
		  button: {
		    text: $( this ).data( 'uploader_button_text' ),
		  },
		  multiple: false
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
		  var attachment = file_frame.state().get('selection').first().toJSON();

		  if ( typeof validateImageSize === 'function' && !validateImageSize( attachment ) ) {
			return;
		  }

		  $("#"+field_id).val(attachment.url);
		  if ( preview_media ) {
		  	$("#"+preview_id).attr('src',attachment.url);
		  }
		});

		// Finally, open the modal
		file_frame.open();
	};

  /* Add/Edit Series Image */
	$('#series_upload_image_button').click(function( event ){
		event.preventDefault();
		var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
		var button_id = button.attr('id');
		var preview_id = button_id.replace( '_upload', '' ).replace( '_button', '_preview' );
		var field_id = button_id.replace( '_upload', '' ).replace( '_button', '_id' );

		// If the media frame already exists, reopen it.
		if ( series_img_frame ) {
		  series_img_frame.open();
		  return;
		}

		// Create the media frame.
		series_img_frame = wp.media({
			title: $( this ).data( 'uploader_title' ),
		  button: {
		    text: $( this ).data( 'uploader_button_text' ),
		  },
			library: {
				type: [ 'image' ]
    	},
		  multiple: false
		});

		series_img_frame.on( 'select', function() {
      // Get media attachment details from the frame state
      var attachment = series_img_frame.state().get('selection').first().toJSON();

      // Send the attachment URL to our custom image input field.
      $('#' + preview_id).attr('src', attachment.url);

      // Send the attachment id to our hidden input
      $('#' + field_id).val(attachment.id);
		});

    // Finally, open the modal on click
    series_img_frame.open();
	});

	/* Remove/clear Series Image */
	$('#series_remove_image_button').click(function( event ){
		event.preventDefault();
		var button = $(this);
		var button_id = button.attr('id');
		var preview_id = button_id.replace( '_remove', '' ).replace( '_button', '_preview' );
		var field_id = button_id.replace( '_remove', '' ).replace( '_button', '_id' );

		if ( confirm('Are you sure?') ) {
        var src = $('#' + preview_id).attr('data-src');
        $('#' + preview_id).attr('src', src);
        $('#' + field_id).val('');
    }
	});

	/* ADD/EDIT EPISODE */

	$('#upload_audio_file_button').click(function( event ){
		event.preventDefault();
		$.fn.ssp_upload_media_file( $(this), false );
	});

	$('#episode_embed_code').click(function() {
		$(this).select();
	});

	$( '.episode_embed_code_size_option' ).change(function() {

		var width = $( '#episode_embed_code_width' ).val();
		var height = $( '#episode_embed_code_height' ).val();
		var post_id = $( '#post_ID' ).val();

		$.post(
		    ajaxurl,
		    {
		        'action': 'update_episode_embed_code',
		        'width': width,
		        'height': height,
		        'post_id': post_id,
		    },
		    function( response ){
		        if( response ) {
		        	$( '#episode_embed_code' ).val( response );
		        	$( '#episode_embed_code' ).select();
		        }
		    }
		);
	});

	/* DATEPICKER */

	$('.ssp-datepicker').datepicker({
		changeMonth: true,
      	changeYear: true,
      	showAnim: 'slideDown',
      	dateFormat: 'd MM, yy',
      	altField: '#date_recorded',
      	altFormat: 'yy-mm-dd',
      	onClose : function ( dateText, obj ) {
		    var d = $.datepicker.parseDate("d MM, yy", dateText);
		    var date = $.datepicker.formatDate("yy-mm-dd", d);
		    var save_field = $(this).attr('id').replace( '_display', '' );
		    $( '#' + save_field ).val( date );
		}
	});

	$('.ssp-datepicker').change( function () {
		var value = $( this ).val();
		if( !value ) {
			var id = $( this ).attr( 'id' );
			var save_field = id.replace( '_display', '' );
			$( '#' + save_field ).val( '' );
		}
	});

	/* SETTINGS PAGE */

	$('#feed-series-toggle').click(function(e) {

		if ( $(this).hasClass( 'series-open' ) ) {
			$('#feed-series-list').slideUp('fast');
			$(this).removeClass( 'series-open' );
			$(this).addClass( 'series-closed' );

		} else if ( $(this).hasClass( 'series-closed' ) ) {
			$('#feed-series-list').slideDown('fast');
			$(this).removeClass( 'series-closed' );
			$(this).addClass( 'series-open' );

		}

	});

	$('#ss_podcasting_data_image_delete').click(function() {
		$( '#ss_podcasting_data_image' ).val( '' );
		$( '#ss_podcasting_data_image_preview' ).attr('src', '');
		return false;
	});

	$('#cover_image_button, #ss_podcasting_data_image_button').click(function (e) {
		var coverImgValidator = function (attachment) {
				return attachment.width === attachment.height && attachment.width >= 300;
			},
			feedImgValidator = function (attachment) {
				var minWidth = 1400,
					maxWidth = 3000;
				return attachment.width >= minWidth &&
					attachment.width <= maxWidth &&
					attachment.height === attachment.width;
			},
			validateImageSize = 'cover_image_button' === $(e.target).prop('id') ? coverImgValidator : feedImgValidator,
			description = $(this).parent().find('.description'),
			$img = 'cover_image_button' === $(e.target).prop('id') ? $('#cover_image_id') : $('#ss_podcasting_data_image_preview');


		$.fn.ssp_upload_media_file($(this), true, validateImageSize);

		description.css('color', '');

		file_frame.on('select', function () {
			var attachment = file_frame.state().get('selection').first().toJSON();
			if (validateImageSize(attachment)) {
				$img.val(attachment.id);
			} else {
				description.css('color', 'red');
			}
		});
	});

	$('#cover_image_delete').click(function() {
		$( '#cover_image, #cover_image_id' ).val( '' );
		$( '#cover_image_preview' ).attr( 'src', '' );
	});

	$('.js-ssp-select2').select2();

	/**
	* Provides possibility to dynamically change the dynamo URL when the episode title or episode podcast is changed.
	* */
	var initDynamoBtn = function () {
		// Make sure it's an episode page and dynamo btn exists
		var $dynamo = $('.ssp-dynamo');
		if (!$dynamo.length) {
			return;
		}

		var $link = $dynamo.find('a'),
			href = $link.attr('href'),
			url = new URL(href),
			changeUrlArg = function (arg, value) {
				url.searchParams.set(arg, value);
				url.search = url.searchParams.toString();
				$link.attr('href', url.toString());
			}

		$(document).on('keyup', '.wp-block-post-title', function (e) {
			var title = $(e.target).text();

			if (title) {
				changeUrlArg('t', title);
			}
		});

		$(document).on('change', '.editor-post-taxonomies__hierarchical-terms-list', function (e) {
			var $target = $(e.target),
				$parent = $target.closest('.editor-post-taxonomies__hierarchical-terms-list');
			if ('Podcasts' !== $parent.attr('aria-label')) {
				return;
			}

			var subtitle = $parent.find('input:checked').first().closest('div').find('label').text();

			if (!subtitle) {
				subtitle = $dynamo.data('default-podcast-title');
			}

			changeUrlArg('s', subtitle)
		});
	}

	initDynamoBtn();
});
