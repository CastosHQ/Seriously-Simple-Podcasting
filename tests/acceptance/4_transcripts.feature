@incomplete
Feature: Seriously Simple Transcripts
	In order to use transcripts feature with the SSP plugin
	As an admin user
	I need to be able to activate and setup the Seriously Simple Transcripts plugin

	Background:
		Given I login as admin
		And I am on the plugins page
		And I can see SST plugin is deactivated

	Scenario: Activate the SST plugin
		Given I want to "Activate the SST plugin"
		When I activate the SST plugin
		Then I can see "Plugin activated."

