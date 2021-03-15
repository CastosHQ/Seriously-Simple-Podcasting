<?php

class LoginCest {
	public function tryLogin( FunctionalTester $I ) {
		$I->amOnPage( '/wp-admin' );
		$I->fillField( '#log', 'admin' );
		$I->fillField( '#pwd', 'password' );
		$I->click( 'Log In' );
		$I->see( 'Welcome to WordPress!', 'h2' );
		// $I->seeEmailIsSent(); // only for Symfony
	}
}
