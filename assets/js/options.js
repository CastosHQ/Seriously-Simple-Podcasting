/**
 * SSP options functions
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery(document).ready(function ($) {
	$('#ssp-options-add-subscribe').on('click', function () {
		// @todo add nonces
		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {action: "insert_new_subscribe_option"}
		})
			.done(function () {
				location.reload();
			});
	});

	$('.delete_subscribe_option').on('click', function (e) {
		e.preventDefault();
		let anchor = $(this);
		// @todo add nonces
		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				action: "delete_subscribe_option",
				option: anchor.data('option')
			}
		})
			.done(function () {
				location.reload();
			});
	});

});
