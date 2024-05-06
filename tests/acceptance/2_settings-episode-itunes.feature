Feature: Create new episode
	In order to use the ITunes episode settings
	As an admin user
	I need to be able to enable this settings

	Background:
		Given I login as admin

	Scenario: "Enable iTunes fields" option works properly
		When I click "Podcast" submenu "Add New Episode"
		Then I can see "Add New Episode"
		And I can not see "iTunes Episode Number:"
		And I can not see "The iTunes Episode Number. Leave Blank If None."
		And I can not see "The iTunes Episode Title. NO Podcast / Show Number Should Be Included."
		And I can not see "iTunes Season Number:"
		And I can not see "The iTunes Season Number. Leave Blank If None."
		And I can not see "iTunes Episode Type:"

		When I click "Podcast" submenu "Settings"
		Then I can see that "General" tab is active
		And I can see "Enable iTunes fields"
		Then I check "Enable iTunes fields" checkbox
		And I save settings

		When I click "Podcast" submenu "Add New Episode"
		Then I can see "Add New Episode"
		And I can see "iTunes Episode Number:"
		And I can see "The iTunes Episode Number. Leave Blank If None."
		And I can see "The iTunes Episode Title. NO Podcast / Show Number Should Be Included."
		And I can see "iTunes Season Number:"
		And I can see "The iTunes Season Number. Leave Blank If None."
		And I can see "iTunes Episode Type:"
