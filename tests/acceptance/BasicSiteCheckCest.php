<?php

class BasicSiteCheckCest
{
	protected $cookies;

    public function _before(AcceptanceTester $I)
    {
    }

    public function checkHomepage(AcceptanceTester $I)
    {
	    $I->amOnPage('/');
	    $I->see('Hello from automated!');
    }

	public function checkAdmin(AcceptanceTester $I)
	{
		$I->amOnPage('/wp-login.php');
		$I->see('Username or Email Address');
		$I->fillField('#user_login', $_ENV['SITE_USER']);
		$I->fillField('#user_pass', $_ENV['SITE_USER_PASS']);
		$I->click('#wp-submit');
		$I->see('Dashboard');
	}
}
