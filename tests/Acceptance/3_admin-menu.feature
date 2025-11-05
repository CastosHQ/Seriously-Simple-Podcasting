Feature: Admin menu
	In order to use the SSP plugin
	As an admin user
	I need to see all the Podcast submenu links

	Scenario: Check that all SSP menu items exist
		When I login as admin
		Then I can see "Podcast"
		And I can see that "All Episodes" submenu exists in "Podcast"
		And I can see that "Add New" submenu exists in "Podcast"
		And I can see that "Tags" submenu exists in "Podcast"
		And I can see that "Podcasts" submenu exists in "Podcast"
		And I can see that "Settings" submenu exists in "Podcast"
		And I can see that "Options" submenu exists in "Podcast"
