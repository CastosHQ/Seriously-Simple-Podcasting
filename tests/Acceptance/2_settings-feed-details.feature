Feature: SSP Plugin Settings
	In order to setup the SSP plugin
	As an admin user
	I need to be able to change feed details plugin settings

	Scenario: All feed details settings exist
		Given I login as admin
		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		When I click tab "Feed details"
		Then I can see that "Feed details" tab is active
		And I can see "This data will be used in the feed for your podcast"
		And I can see "View feed"
		And I can see "Title"
		And I can see "Subtitle"
		And I can see "Host"
		And I can see "Primary Category"
		And I can see "Primary Sub-Category"
		And I can see "Secondary Category"
		And I can see "Secondary Sub-Category"
		And I can see "Tertiary Category"
		And I can see "Tertiary Sub-Category"
		And I can see "Description/Summary"
		And I can see "Cover Image"
		And I can see "Owner name"
		And I can see "Language"
		And I can see "Copyright"
		And I can see "Podcast funding"
		And I can see "Explicit"
		And I can see "Complete"
		And I can see "Locked"
		And I can see "Source for publish date"
		And I can see "Show Type"
		And I can see "Media File Prefix"
		And I can see "Episode description"
		And I can see "Turbocharge podcast feed"
		And I can see "Redirect this feed to new URL"
		And I can see "New podcast feed URL"
		And I can see "Subscribe button links"
		And I can see "Apple Podcasts URL"
		And I can see "Stitcher URL"
		And I can see "Google Podcasts URL"
		And I can see "Spotify URL"
		And I can see that discount widget exists
