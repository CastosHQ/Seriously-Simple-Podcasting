Feature: Onboarding Wizard
	In order to initially setup the SSP plugin
	As an admin user
	I need to be able to setup it via onboarding wizard

	Scenario: Setup the SSP plugin via onboarding wizard
		Given I login as admin
		And I am on the plugins page
		And I can see SSP plugin is deactivated
		When I activate the SSP plugin
		Then I can see the Onboarding Wizard
		And I can see that I am on the "Welcome" step
		When I fill the "Show name" with "Automated test show"
		And I fill the "Show description" with "This show is to test some SSP functionality"
		And I click "Proceed" button
		Then I can see that I am on the "Cover" step
		When I click "Skip" link
		Then I can see that I am on the "Categories" step
		When I select the "Primary Category" as "Business"
		And I select the "Primary Sub-Category" as "Management"
		And I click "Proceed" button
		Then I can see that I am on the "Hosting" step
		When I click "Not interested right now." link
		Then I can see that I am on the "Done!" step
		When I go to step "Categories"
		Then I can see "Business" selected as "Primary Category"
		And I can see "Management" selected as "Primary Sub-Category"
		When I go to step "Welcome"
		Then I can see field "Show name" contains "Automated test show"
		And I can see field "Show description" contains "This show is to test some SSP functionality"
		When I go to the plugins page
		Then I can see SSP plugin is activated
		When I deactivate the SSP plugin
		And I activate the SSP plugin
		Then I can not see the Onboarding Wizard
		When I click SSP submenu "Settings"
		Then I can see "Podcast Settings"
		When I click tab "Feed details"
		Then I can see that "Feed details" tab is active
		And I can see field "Feed details Title" contains "Automated test show"
		And I can see field "Feed details Description/Summary" contains "This show is to test some SSP functionality"
		Then I can see "Business" selected as "Feed details Primary Category"
		And I can see "Management" selected as "Feed details Primary Sub-Category"
