jQuery(document).ready(function($) {
	$('.js-onboarding-delete-img-info').click(function(){
		$('.js-onboarding-img-val').val('');
		$('.js-onboarding-img-info').hide();
	});

	$('#ss_podcasting_data_image').change(function(){
		$('.js-onboarding-img-info').show();
	});

	var $fields = $('.js-onboarding-field'),
		$btn = $('.js-onboarding-btn'),
		validateOnboarding = function () {
			var valid = true;
			$fields.each(function () {
				if (!$(this).val()) {
					valid = false;
					return false;
				}
			});

			if (valid) {
				$btn.removeAttr('disabled');
			} else {
				$btn.attr('disabled', 'disabled');
			}
		};

	$fields.on('change paste keydown keyup', validateOnboarding);
	validateOnboarding();
});
