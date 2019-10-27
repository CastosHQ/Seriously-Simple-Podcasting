<?php

class ValidateApiKeyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function testItCanValidateApiKey(AcceptanceTester $I)
    {
    	$I->loginAsAdmin();
    	$I->amOnPage('wp-admin/edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting');
    	$I->see('Connect your WordPress site to your Castos account');
    }
}
