Feature: SSP Plugin Hosting Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change hosting plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Hosting"
		Then I can see that "Hosting" tab is active

	Scenario: All hosting settings exist
		Then I can see "Podcast Hosting"
		And I can see "Your email"
		And I can see "The email address you used to register your Castos account."
		And I can see "Castos API key"
		And I can see "Your Castos API key. Available from your Castos account dashboard."
		And I can see "Verify Credentials."
