jQuery(document).ready(function($) {
	$('.js-onboarding-delete-img-info').click(function(){
		$('.js-onboarding-img-val').val('');
		$('.js-onboarding-img-info').hide();
	});

	$('#ss_podcasting_data_image').change(function(){
		console.log('Changed!');
		$('.js-onboarding-img-info').show();
	});
});
