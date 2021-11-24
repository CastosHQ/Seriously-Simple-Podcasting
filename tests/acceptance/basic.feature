Feature: Basic site tests
  In order to test the SSP plugin
  As an admin user
  I need to be able to login

  Scenario: Login
	  When I am on the homepage
	  Then I can see "Hello from automated" text
	  When I login as admin
	  Then I can see "Dashboard"
