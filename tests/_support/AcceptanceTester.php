<?php

use Codeception\Step\Argument\PasswordArgument;
use function PHPUnit\Framework\assertTrue;


/**
 * Because we test on remote server, standard functions like loginAsAdmin can not be used,
 * so lets rewrite them with user steps.
 *
 * Inherited Methods
 * @method void wantToTest( $text )
 * @method void wantTo( $text )
 * @method void execute( $callable )
 * @method void expectTo( $prediction )
 * @method void expect( $prediction )
 * @method void amGoingTo( $argumentation )
 * @method void am( $role )
 * @method void lookForwardTo( $achieveValue )
 * @method void comment( $description )
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor {
	use _generated\AcceptanceTesterActions;

	/**
	 * Define custom actions here
	 */
	public function loginAsAdmin() {
		$this->amOnPage( '/wp-login.php' );
		$this->see( 'Username or Email Address' );
		$this->fillField( '#user_login', new PasswordArgument( $_ENV['SITE_USER'] ) );
		$this->fillField( '#user_pass', new PasswordArgument( $_ENV['SITE_USER_PASS'] ) );
		$this->click( '#wp-submit' );
	}

	public function amOnPluginsPage() {
		$this->amOnPage( '/wp-admin/plugins.php' );
	}

	// Gherkin functions.

	/**
	 * @Given I am on the plugins page
	 * @Given I go to the plugins page
	 */
	public function iAmOnThePluginsPage() {
		$this->amOnPluginsPage();
	}

	/**
	 * @When I am on the homepage
	 */
	public function iAmOnTheHomepage() {
		$this->amOnPage( '/' );
	}

	/**
	 * @When I login as admin
	 */
	public function iLoginAsAdmin() {
		$this->loginAsAdmin();
	}

	/**
	 * @Then I can see :arg1
	 * @Then I can see :arg1 text
	 */
	public function iCanSee( $arg1 ) {
		$this->see( $arg1 );
	}

	/**
	 * @Given I can see SSP plugin is deactivated
	 */
	public function iCanSeeSspIsDeactivated() {
		$this->see( 'Activate', '#activate-seriously-simple-podcasting' );
	}

	/**
	 * @When I activate the SSP plugin
	 */
	public function iActivateTheSSPPlugin() {
		$this->click( '#activate-seriously-simple-podcasting' );
		$this->wait( 2 );
	}

	/**
	 * @Then I can see SSP plugin is activated
	 */
	public function iCanSeeSSPPIsActivated()
	{
		$this->see( 'Deactivate', '#deactivate-seriously-simple-podcasting' );
	}

	/**
	 * @When I deactivate the SSP plugin
	 */
	public function iDeactivateTheSSPPlugin()
	{
		$this->click( '#deactivate-seriously-simple-podcasting' );
		$this->wait( 2 );
	}

	/**
	 * @Then I can see the Onboarding Wizard
	 */
	public function iCanSeeTheOnboardingWizard() {
		$this->see( "Let's get your podcast started" );
	}

	/**
	 * @Then I can not see the Onboarding Wizard
	 */
	public function iCanNotSeeTheOnboardingWizard()
	{
		$this->dontSee( "Let's get your podcast started" );
	}

	/**
	 * @Then I can see that I am on the :arg1 step
	 */
	public function iCanSeeThatIAmOnTheStep( $arg1 ) {
		$this->see( $arg1, '.ssp-onboarding__step.active' );
	}

	/**
	 * @When I click :arg1 link
	 */
	public function iClickLink( $arg1 ) {
		$this->click( $arg1, 'a' );
	}

	/**
	 * @When I fill the :arg1 with :arg2
	 */
	public function iFillTheFieldWith( $arg1, $arg2 ) {
		$map = $this->getFieldsMap();

		assertTrue( array_key_exists( $arg1, $map ) );

		$this->fillField( $map[ $arg1 ], $arg2 );
	}


	/**
	 * @When I click :arg1 button
	 */
	public function iClickButton( $arg1 ) {
		$this->click( $arg1, 'button' );
	}

	/**
	 * @When I select the :arg1 as :arg2
	 */
	public function iSelectTheFieldOption( $arg1, $arg2 ) {
		$map = $this->getFieldsMap();

		assertTrue( array_key_exists( $arg1, $map ) );

		$this->selectOption( $map[ $arg1 ], $arg2 );
	}

	public function getFieldsMap() {
		return array(
			'Show name'                         => '#show_name',
			'Show description'                  => '#show_description',
			'Primary Category'                  => '#data_category',
			'Primary Sub-Category'              => '#data_subcategory',
			'Feed details Title'                => '#data_title',
			'Feed details Description/Summary'  => '#data_description',
			'Feed details Primary Category'     => '#data_category',
			'Feed details Primary Sub-Category' => '#data_subcategory',
		);
	}

	/**
	 * @Then I can see field :arg1 contains :arg2
	 */
	public function iCanSeeFieldArgContains( $arg1, $arg2 ) {
		$map = $this->getFieldsMap();
		assertTrue( array_key_exists( $arg1, $map ) );

		$this->seeInField( $map[ $arg1 ], $arg2 );
	}

	/**
	 * @When I go to step :arg1
	 */
	public function iGoToStepNumber( $arg1 ) {
		$this->click( $arg1, '.ssp-onboarding__step a' );
	}

	/**
	 * @Then I can see :arg1 selected as :arg2
	 */
	public function iCanSeeOptionSelectedAs($arg1, $arg2)
	{
		$map = $this->getFieldsMap();
		assertTrue( array_key_exists( $arg2, $map ) );

		$this->seeOptionIsSelected( $map[ $arg2 ], $arg1 );
	}


	/**
	 * @When I click SSP submenu :arg1
	 */
	public function iClickSSPSubmenuArg($arg1)
	{
		$this->click( $arg1, '#menu-posts-podcast ul li a' );
	}

	/**
	 * @When I click tab :arg1
	 */
	public function iClickTabArg($arg1)
	{
		$this->click( $arg1, '#main-settings a.nav-tab' );
	}

	/**
	 * @Then I can see that :arg1 tab is active
	 */
	public function iCanSeeTabIsActive($arg1)
	{
		$this->see( $arg1, '#main-settings a.nav-tab-active' );
	}
}
