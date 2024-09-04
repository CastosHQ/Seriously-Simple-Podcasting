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
		And I can see "Castos API token"
		And I can see "Your Castos API token. Available from your"
		And I can see "Connect"
