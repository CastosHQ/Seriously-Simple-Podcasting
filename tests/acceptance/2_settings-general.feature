Feature: SSP Plugin General Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change general plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active

	Scenario: All general settings exist
		Then I can see "Podcast post types"
		And I can see "Include podcast in main blog"
		And I can see "Enable iTunes fields"
		And I can see that discount widget exists

	Scenario: Change podcast post types
		When I check "Podcast post types Posts" checkbox
		And I save settings
		Then I can see that "Podcasts" submenu exists in "Posts"

