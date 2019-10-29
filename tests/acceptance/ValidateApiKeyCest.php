<?php

class ValidateApiKeyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function testItCanValidateApiKey(AcceptanceTester $I)
    {
    	$email = 'jonathan@castos.com';
		$apiKey = '2y10U8GdHpFsIndddWDB52cO8r859w4PVzoha0iNXY2eiQqosvkOap6';

    	$I->loginAsAdmin();
    	$I->amOnPage('wp-admin/edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting');
    	$I->see('Connect your WordPress site to your Castos account');
    	$I->fillField('#podmotor_account_email', $email);
    	$I->fillField('#podmotor_account_api_token', $apiKey);
    	$I->click('Validate Credentials');
    	$I->waitForElement('.validate-api-credentials-message');
    	$I->see('Credentials Valid');
    }
}
