Feature: Login
	In order to test the SSP plugin
	As an admin user
	I need to be able to login

	Scenario: Log admin user
		When I login as admin
		Then I can see "Dashboard"
