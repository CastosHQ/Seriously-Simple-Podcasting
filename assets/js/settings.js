/**
 * SSP settings functions
 * Created by Jonathan Bossenger on 2017/01/20.
 * Updated by Sergey Zakharchenko from 2021
 */

jQuery(document).ready(function($) {

	var $podmotorAccountEmail = $("#podmotor_account_email"),
		$podmotorAccountAPIToken = $("#podmotor_account_api_token"),
		$parentCategories = $('.js-parent-category'),
		$validateBtn = $("#validate_api_credentials");

	function disableSubmitButton(){
		/**
		 * If either API field is empty, disable the submit button
		 */
		if ( $podmotorAccountEmail.val() === '' || $podmotorAccountAPIToken.val() === ''  ){
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		}

		/**
		 * If the user changes the email, disable the submit button
		 */
		$podmotorAccountEmail.on("change paste keydown keyup", function() {
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		});

		/**
		 * If the user changes the account api key, disable the submit button
		 */
		$podmotorAccountAPIToken.on("change paste keydown keyup", function() {
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		});
	}

	/**
	 * Validate the api credentials
	 */
	function validateAPICredentials(){
		$validateBtn.on("click", function(){

			$(".validate-api-credentials-message").html( "Validating API credentials..." );

			var podmotor_account_email = $("#podmotor_account_email").val(),
				podmotor_account_api_token = $("#podmotor_account_api_token").val(),
				nonce = $("#podcast_settings_tab_nonce").val();

			$validateBtn.addClass('loader');

			$.ajax({
				method: "GET",
				url: ajaxurl,
				data: {
					action: "validate_castos_credentials",
					api_token: podmotor_account_api_token,
					email: podmotor_account_email,
					nonce: nonce
				}
			})
				.done(function( response ) {
					$validateBtn.removeClass('loader');
					if (response.status === 'success') {
						$(".validate-api-credentials-message").html("Credentials Valid. Please click 'Save Settings' to save Credentials.");
						$("#ssp-settings-submit").prop("disabled", "");
						$validateBtn.val('Valid Credentials');
						$validateBtn.addClass('valid');
					} else {
						$validateBtn.addClass('invalid');
						$(".validate-api-credentials-message").html(response.message);
					}
					$validateBtn.trigger('validated');
				});

		});
	}

	/**
	 * Disconnect Castos checkbox on change, renders a confirmation message to the user.
	 */
	function disconnectCastos(){
		$('#podmotor_disconnect').on('change', function (event) {
			var $checkbox = $(this);

			// if the change is to uncheck the checkbox
			if (!$checkbox.is(':checked')) {
				return;
			}

			var $message = 'If you disconnect from Castos hosting you will no longer be able to upload media files to the Castos hosting platform. If you’re no longer a Castos customer your media files may no longer be available to your listeners.';
			var user_input = confirm($message);
			if (user_input !== true) {
				// Ensures this code runs AFTER the browser handles click however it wants.
				setTimeout(function() {
					$checkbox.removeAttr('checked');
				}, 0);
				event.preventDefault();
				event.stopPropagation();
			}
		});
	}

	/**
	 * Show only options related to parent category
	 */
	function filterSubcategoryGroups() {
		var $parent = $(this),
			subcategoryID = $parent.data('subcategory'),
			parentCategory = $parent.find('option:selected').text();

		if (!subcategoryID || !parentCategory) return false;

		var $subcategory = $('#' + subcategoryID);
		$subcategory.find('optgroup').hide();
		var $selectedOptgroup = $subcategory.find('optgroup[label="' + parentCategory + '"]');

		if (!$selectedOptgroup.length || '-- None --' === parentCategory) {
			$subcategory.val('');
		} else {
			$selectedOptgroup.show();
		}
	}

	if ($podmotorAccountEmail.length > 0 && $podmotorAccountAPIToken.length > 0) {
		disableSubmitButton();
		validateAPICredentials();
		disconnectCastos();
	}

	if ($parentCategories.length) {
		$parentCategories.each(filterSubcategoryGroups);
		$parentCategories.on('change', filterSubcategoryGroups);
	}
});
