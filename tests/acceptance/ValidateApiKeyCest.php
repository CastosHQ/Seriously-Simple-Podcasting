<?php

class ValidateApiKeyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @skip
     * */
	public function testItCanValidateApiKey( AcceptanceTester $I ) {
		$email  = $_ENV['CASTOS_USER_EMAIL'];
		$apiKey = $_ENV['CASTOS_USER_KEY'];

		$I->loginAsAdmin();
		$I->amOnPage( 'wp-admin/edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_settings&tab=castos-hosting' );
		$I->see( 'Connect your WordPress site to your Castos account' );
		$I->fillField( '#podmotor_account_email', $email );
		$I->fillField( '#podmotor_account_api_token', $apiKey );
		$I->click( 'Validate Credentials' );
		$I->wait( 1 );
		$I->see( 'Validating API credentials...' );
		$I->waitForElement( '.validate-api-credentials-message' );
		$I->see( 'Disconnect Castos' );
	}
}
