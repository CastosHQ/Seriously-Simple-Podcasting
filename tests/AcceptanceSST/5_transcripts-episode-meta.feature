@incomplete
Feature: Seriously Simple Transcripts
	In order to use transcripts feature with the Seriously Simple Transcripts plugin
	As an admin user
	I need to be able to upload the transcripts file on the episode settings page

	Background:
		Given I login as admin
		Given I login as admin

	Scenario: Make sure that "Transcript file" setting exists
		Given I want to "make sure that Transcript file setting exists"
		When I click "Podcast" submenu "All Episodes"
		And I click "Add New Episode" link
		Then I can see "Transcript file:"
		And I can see "Upload the transcript file or paste the file URL here."
		And I can see "To show the transcript file in the feed, please use SRT, VTT, JSON, HTML or TXT files."
		And I can see "To add a download transcript link, enable player meta data"

		Then I want to "make sure that the helper text appears after player meta data is disabled."
		When I click "Podcast" submenu "Settings"
		And I click tab "Player"
		Then I can see "Player Settings"
		And I can see "Enable Player meta data"
		And I can see "Turn this on to enable player meta data underneath the player (download link, episode duration, date recorded, etc.)."
		And I can see that checkbox for "Enable Player meta data" label is checked
		When I uncheck checkbox with "Enable Player meta data" label
		And I save settings
		Then I don't see that checkbox for "Enable Player meta data" label is checked
		And I can not see "Show download transcript link"
		When I click "Podcast" submenu "All Episodes"
		And I click "Add New Episode" link
		Then I can see "Transcript file:"
		And I can see "To add a download transcript link, enable player meta data"

		Then I want to "revert Transcript settings back"
		When I click "Podcast" submenu "Settings"
		And I click tab "Player"
		Then I don't see that checkbox for "Enable Player meta data" label is checked
		When I check checkbox with "Enable Player meta data" label
		And I save settings
		Then I can see that checkbox for "Enable Player meta data" label is checked

