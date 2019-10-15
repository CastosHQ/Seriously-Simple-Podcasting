/**
 * SSP options functions
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery(document).ready(function ($) {
	$('#ssp-options-add-subscribe').on('click', function () {
		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				_ajax_nonce: options_ajax_object.nonce,
				action: "insert_new_subscribe_option"
			}
		})
			.done(function () {
				location.reload();
			});
	});

	$('.delete_subscribe_option').on('click', function (e) {
		e.preventDefault();
		var anchor = $(this);
		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				_ajax_nonce: options_ajax_object.nonce,
				action: "delete_subscribe_option",
				count: anchor.data('count'),
				option: anchor.data('option'),
			}
		})
			.done(function () {
				location.reload();
			});
	});

});
