Feature: Login
	In order to use the default podcast
	As an admin user
	I need to make sure the default podcast exists on plugin activation

	Background:
		Given I login as admin
		And I am on the plugins page
		And I can see SSP plugin is deactivated
		Then I activate the SSP plugin

	Scenario: Check the default feed
		When I go to the "/wp-admin/edit-tags.php?taxonomy=series&post_type=podcast"
		Then I can see "My WordPress (default)"
		And I can see "my-wordpress"
		And I can see "/feed/podcast/my-wordpress"

		Given I want to "Be redirected from the old default feed URL to the term-based URL"
		When I go to the "/feed/podcast"
		Then I can see that current url is "/feed/podcast/my-wordpress"
		When I go to the "/feed/podcast/not-existing-feed"
		Then I can see that current url is "/feed/podcast/my-wordpress"
		Then I am on the plugins page
		And I deactivate the SSP plugin
