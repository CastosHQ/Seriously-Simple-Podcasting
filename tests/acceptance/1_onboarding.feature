Feature: Onboarding Wizard
	In order to initially setup the SSP plugin
	As an admin user
	I need to be able to setup it via onboarding wizard

	Background:
		Given I login as admin
		And I am on the plugins page
		And I can see SSP plugin is deactivated

	Scenario: Setup main settings via onboarding wizard
		Given I want to "Get to onboarding wizard when plugin is first activated"
		When I activate the SSP plugin
		Then I can see the Onboarding Wizard
		And I can see that I am on the "Welcome" step of onboarding wizard

		Given I want to "Setup the first step of onboarding wizard"
		When I fill the "Show name" with "Automated test show"
		And I fill the "Show description" with "This show is to test some SSP functionality"
		And I click "Proceed" button
		Then I can see that I am on the "Cover" step of onboarding wizard

		Given I want to "Skip the second step of onboarding wizard"
		When I click "Skip" link
		Then I can see that I am on the "Categories" step of onboarding wizard

		Given I want to "Setup the third step of onboarding wizard"
		When I select the "Primary Category" as "Business"
		And I select the "Primary Sub-Category" as "Management"
		And I click "Proceed" button
		Then I can see that I am on the "Hosting" step of onboarding wizard

		Given I want to "Setup the fourth step of onboarding wizard"
		When I click "Skip Step" link
		Then I can see that I am on the "Done!" step of onboarding wizard

		Given I want to "Check that Categories step was setup correctly"
		When I go to step "Categories"
		Then I can see "Business" selected as "Primary Category"
		And I can see "Management" selected as "Primary Sub-Category"

		Given I want to "Check that Welcome step was setup correctly"
		When I go to step "Welcome"
		Then I can see field "Show name" contains "Automated test show"
		And I can see field "Show description" contains "This show is to test some SSP functionality"

		Given I want to "Check that onboarding wizard do not appear after initial setup"
		When I go to the plugins page
		Then I can see SSP plugin is activated
		When I deactivate the SSP plugin
		And I activate the SSP plugin
		Then I can not see the Onboarding Wizard

		Given I want to "Check that onboarding widget setup changed the plugin settings"
		When I click "Podcast" submenu "Settings"
		Then I can see "Podcast Settings"
		When I click tab "Feed details"
		Then I can see that "Feed details" tab is active
		And I can see "Automated test show (default)"
		And I can see field "Feed details Title" contains "Automated test show"
		And I can see field "Feed details Description/Summary" contains "This show is to test some SSP functionality"
		Then I can see "Business" selected as "Feed details Primary Category"
		And I can see "Management" selected as "Feed details Primary Sub-Category"

		Given I want to "Check that onboarding changed the default podcast name"
		When I go to the "/wp-admin/edit-tags.php?taxonomy=series&post_type=podcast"
		Then I can see "Automated test show (default)"
		And I can see "automated-test-show"
		And I can see "/feed/podcast/automated-test-show"
