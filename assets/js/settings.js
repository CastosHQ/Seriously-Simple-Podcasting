/**
 * SSP settings functions
 * Created by Jonathan Bossenger on 2017/01/20.
 */

jQuery(document).ready(function($) {

	if ( $("#podmotor_account_email").length > 0 && $("#podmotor_account_api_token").length > 0 ){

		/**
		 * Disable the account id field
		 */
		$("#podmotor_account_id").prop( "readonly", "readonly" );

		/**
		 * If either API field is empty, disable the submit button
		 */
		if ( $("#podmotor_account_email").val() == '' || $("#podmotor_account_api_token").val() == ''  ){
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		}

		/**
		 * If the user changes the email, disable the submit button
		 */
		$("#podmotor_account_email").on("change paste keydown keyup", function() {
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		});

		/**
		 * If the user changes the account api key, disable the submit button
		 */
		$("#podmotor_account_api_token").on("change paste keydown keyup", function() {
			$("#ssp-settings-submit").prop( "disabled", "disabled" );
		});

		/**
		 * Validate the api credentials
		 */
		$("#validate_api_credentials").on("click", function(){

			$(".validate-api-credentials-message").html( "Validating API credentials..." );

			var podmotor_account_email = $("#podmotor_account_email").val();
			var podmotor_account_api_token = $("#podmotor_account_api_token").val();

			$.ajax({
				method: "GET",
				url: ajaxurl,
				data: { action: "validate_castos_credentials", api_token: podmotor_account_api_token, email: podmotor_account_email }
			})
			.done(function( response ) {
				if ( response.status == 'success' ){
					$(".validate-api-credentials-message").html( "Credentials Valid" );
					$("#podmotor_account_id").val( response.podmotor_id );
					$("#podmotor_account_id").attr( 'value', response.podmotor_id );
					$("#ssp-settings-submit").prop( "disabled", "" );
				}else {
					$(".validate-api-credentials-message").html( response.message );
				}
			});

		});

		/**
		 * Disconnect Castos checkbox on change, renders a confirmation message to the user.
		 */
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

});
