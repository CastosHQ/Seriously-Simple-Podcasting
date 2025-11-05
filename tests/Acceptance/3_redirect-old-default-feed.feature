Feature: Login
	In order to use the default podcast
	As a site visitor
	I need to be redirected to the new default podcast feed URL

	Scenario: Check the main feed
		When I go to the "/feed/podcast"
		Then I can see that current url is "/feed/podcast/automated-test-show"
		And I can see "Automated test show"
		And I can see "Episode1"

