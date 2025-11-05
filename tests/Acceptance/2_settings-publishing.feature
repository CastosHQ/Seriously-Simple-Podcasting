Feature: SSP Plugin Publishing Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change publishing plugin settings

	Background:
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Publishing"
		Then I can see that "Publishing" tab is active

	Scenario: All publishing settings exist
		Then I can see "Use these URLs to share and publish your podcast feed"
		And I can see "External feed URL"
		And I can see "If you are syndicating your podcast using a third-party service"
		And I can see "Your RSS feeds"
		And I can see "/feed/podcast"
		And I can see "Podcast page"
		And I can see "/podcast"
		And I can see that discount widget exists


