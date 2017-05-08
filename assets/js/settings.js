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
                data: { action: "validate_podmotor_api_credentials", api_token: podmotor_account_api_token, email: podmotor_account_email }
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

        })

    }

});
