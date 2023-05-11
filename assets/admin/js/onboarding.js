jQuery(document).ready(function($) {
	var $imgInfo = $('.js-onboarding-img-info'),
		$preview = $imgInfo.find('.js-onboarding-img'),
		$imgInput = $imgInfo.find('.js-onboarding-img-val'),
		$imgName = $imgInfo.find('.js-onboarding-img-name'),
		$fields = $('.js-onboarding-field'),
		$btn = $('.js-onboarding-btn'),
		$validateTokenBtn = $('.js-onboarding-validate-token'),
		$hostingStep2 = $('.js-hosting-form'),
		$accordion = $('.js-accordion'),
		$dragable = $('.js-onboarding-dragable'),
		$uploadImageBtn = $('#ss_podcasting_data_image_button'),
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
		},
		initDeleteImgInfo = function(){
			$imgInfo.find('.js-onboarding-delete-img-info').click(function(){
				$imgInput.val('');
				$imgInfo.hide();
				validateOnboarding();
			});
		},
		baseName = function (str) {
			var base = String(str).substring(str.lastIndexOf('/') + 1);
			if( base.length > 20 ){
				base = '..' + base.substring(base.length - 20, base.length);
			}
			return base;
		},
		updateImageName = function(){
			var imageUrl = $imgInput.val();
			if( imageUrl ){
				$imgName.html(baseName(imageUrl));
				$imgInfo.show();
			}
		},
		initImgPreview = function(){
			updateImageName();
			$preview.on('load', function(){
				validateOnboarding();
				updateImageName();
			});
		},
		initTokenValidation = function(){
			$validateTokenBtn.on('validated', function () {
				//don't use $btn since it has custom validation
				var $form = $validateTokenBtn.closest('form'),
					$nextButton = $form.find('button[type=submit]');
				$(this).removeClass('validating');
				if ($validateTokenBtn.hasClass('valid')) {
					$nextButton.removeAttr('disabled');
					$form.find('.validate-api-credentials-message').html('');
					$(this).html($(this).data('valid-txt'));
				} else {
					$(this).html($(this).data('initial-txt'));
					$nextButton.attr('disabled', 'disabled');
				}
			});

			$validateTokenBtn.on('click', function(){
				$(this).addClass('validating').html($(this).data('validating-txt'));
			});

			$('.js-onboarding-validate-token-field').on('change paste keyup', function(){
				var $nextButton = $validateTokenBtn.closest('form').find('button[type=submit]');
				$validateTokenBtn.html($validateTokenBtn.data('initial-txt'));
				$validateTokenBtn.removeClass('valid');
				$nextButton.attr('disabled', 'disabled');
			});
		},
		initOnboardingValidation = function(){
			$fields.on('change paste keyup', validateOnboarding);
			validateOnboarding();
		},
		initHostingConnectionSteps = function(){
			$accordion.click(function () {
				let openedClass = 'ssp-onboarding-step-4__accordion--opened',
					openedFormClass = 'ssp-onboarding-step-4__form--opened';
				if ($accordion.hasClass(openedClass)) {
					$accordion.removeClass(openedClass);
					$hostingStep2.removeClass(openedFormClass);
				} else {
					$accordion.addClass(openedClass);
					$hostingStep2.addClass(openedFormClass);
				}
			});
		},
		initDragableImage = function () {
			$dragable.on('dragover', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.on('dragenter', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.on('drop', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.click(function(){
				$uploadImageBtn.trigger('click');
			});
		},
		init = function(){
			initDeleteImgInfo();
			initImgPreview();
			initTokenValidation();
			initOnboardingValidation();
			initHostingConnectionSteps();
			initDragableImage();
		}


	init();
});
