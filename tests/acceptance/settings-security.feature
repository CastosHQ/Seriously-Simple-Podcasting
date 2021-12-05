Feature: SSP Plugin Security Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change security plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Security"
		Then I can see that "Security" tab is active

	Scenario: All security settings exist
		Then I can see "Change these settings to ensure that your podcast feed remains private"
		And I can see "Password protect your podcast feed"
		And I can see "Mark if you would like to password protect your podcast"
		And I can see "Username"
		And I can see "Username for your podcast feed."
		And I can see "Password"
		And I can see "Password for your podcast feed."
		And I can see "This message will be displayed to people"
		And I can see that discount widget exists
