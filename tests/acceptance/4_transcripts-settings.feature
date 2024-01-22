@incomplete
Feature: Seriously Simple Transcripts
	In order to use transcripts feature with the SSP plugin
	As an admin user
	I need to be able to setup the Seriously Simple Transcripts plugin

	Background:
		Given I login as admin
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active

	Scenario: Make sure that "Show download transcript link" setting exists
		Given I want to "make sure that Show download transcript link exists"
		When I click tab "Player"
		Then I can see that "Player" tab is active
		And I can see "Show download transcript link"
		And I can see "Turn on to display the download transcript link"
		And I can see that checkbox for "Show download transcript link" label is checked



