Feature: Login
	In order to use the default podcast
	As an admin user
	I need to make sure the default podcast exists on plugin activation

	Background:
		Given I login as admin

	Scenario: Check the default feed
		When I go to the "/wp-admin/edit-tags.php?taxonomy=series&post_type=podcast"
		Then I can see "Automated test show (default)"
		And I can see "automated-test-show"
		And I can see "/feed/podcast/automated-test-show"

		Given I want to "Be redirected from the old default feed URL to the term-based URL"
		When I go to the "/feed/podcast"
		Then I can see that current url is "/feed/podcast/automated-test-show"
		When I go to the "/feed/podcast/not-existing-feed"
		Then I can see that current url is "/feed/podcast/automated-test-show"
