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
		Then I can see "Connect your WordPress site to your"
		And I can see "Your email"
		And I can see "The email address you used to register your Castos account."
		And I can see "Castos API token"
		And I can see "Your Castos API token. Available from your Castos account dashboard."
		And I can see "Disconnect Castos"
		And I can see "Disconnect your Castos account."
