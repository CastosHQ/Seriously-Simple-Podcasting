Feature: SSP Plugin Redirection Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change redirection plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Redirection"
		Then I can see that "Redirection" tab is active

	Scenario: All redirection settings exist
		Then I can see "Use these settings to safely move your podcast to a different location"
		And I can see "Redirect podcast feed to new URL"
		And I can see "Redirect your feed to a new URL (specified below)."
		And I can see "New podcast feed URL"
		And I can see "Your podcast feed's new URL."
		And I can see that discount widget exists

