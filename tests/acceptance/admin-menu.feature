Feature: Admin menu
	In order to use the SSP plugin
	As an admin user
	I need to see all the Podcast submenu links

	Scenario: Log admin user
		When I login as admin
		Then I can see "Podcast"
		Then I can see that "All Episodes" submenu exists in "Podcast"
		Then I can see that "Add New" submenu exists in "Podcast"
		Then I can see that "Tags" submenu exists in "Podcast"
		Then I can see that "Series" submenu exists in "Podcast"
		Then I can see that "Settings" submenu exists in "Podcast"
		Then I can see that "Options" submenu exists in "Podcast"
