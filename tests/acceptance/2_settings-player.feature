Feature: SSP Plugin Player Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change player plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Player"
		Then I can see that "Player" tab is active

	Scenario: All player settings exist
		And I can see "Player Settings"
		And I can see "Media player locations"
		And I can see "Media player position"
		And I can see "Media player visibility"
		And I can see "Enable Player meta data"
		And I can see "Show subscribe urls"
		And I can see "Media player style"
		And I can see "Player mode"
		And I can see "Player mode"
		And I can see "Show subscribe button"
		And I can see "Show share button"
		And I can see "Show download file link"
		And I can see "Show play in new window link"
		And I can see "Show duration"
		And I can see "Show recorded date"
		And I can see that discount widget exists




