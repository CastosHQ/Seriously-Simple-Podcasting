jQuery(document).ready(function($) {
	var $imgInfo = $('.js-onboarding-img-info'),
		$preview = $imgInfo.find('.js-onboarding-img'),
		$imgInput = $imgInfo.find('.js-onboarding-img-val');

	$imgInfo.find('.js-onboarding-delete-img-info').click(function(){
		$imgInput.val('');
		$imgInfo.hide();
		validateOnboarding();
	});

	if( $imgInput.val() ){
		$imgInfo.show();
	}

	$preview.on('load', function(){
		validateOnboarding();
		if( $imgInput.val() ){
			$imgInfo.show();
		}
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
