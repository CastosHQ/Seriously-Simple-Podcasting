Feature: Create new episode
	In order to use the SSP plugin
	As an admin user
	I need to be able to add new episodes

	Background:
		Given I login as admin

	Scenario: Create new episode
		When I click "Podcast" submenu "Add New"
		Then I can see "Add New Episode"
		And I can see "Podcast Episode Details"
		And I can see "Episode type:"
		And I can see "Audio"
		And I can see "Video"
		And I can see "Episode file:"
		And I can see "Upload audio episode files as MP3 or M4A, video episodes as MP4, or paste the file URL."
		And I can see "Episode Image:"
		And I can see "The episode image should be square to display properly in podcasting apps and directories, and should be at least 300x300px in size."
		And I can see "Duration:"
		And I can see "Duration of podcast file for display (calculated automatically if possible)."
		And I can see "File size:"
		And I can see "Size of the podcast file for display (calculated automatically if possible)."
		And I can see "Date recorded:"
		And I can see "The date on which this episode was recorded."
		And I can see "Mark this episode as explicit."
		And I can see "Block this episode from appearing in the iTunes & Google Play podcast libraries."

		And I can see "iTunes Episode Number:"
		And I can see "The iTunes Episode Number. Leave Blank If None."
		And I can see "The iTunes Episode Title. NO Podcast / Show Number Should Be Included."
		And I can see "iTunes Season Number:"
		And I can see "The iTunes Season Number. Leave Blank If None."
		And I can see "iTunes Episode Type:"

		When I fill the "Episode title" with "Test episode"
		And I fill the "Episode content" with "Test episode content"
		And I fill the "Episode file" with "https://episodes.castos.com/podcasthacker/d21a1b7a-531f-48f1-b4c0-8b8add2bccfe-file-example.mp3"
		And I fill the "iTunes Episode Number" with "123"
		And I fill the "iTunes Episode Title" with "234"
		And I fill the "iTunes Season Number" with "345"
		And I select the "iTunes Episode Type" as "Full: For Normal Episodes"

		And I save the episode
		Then I can see "Episode published."
		And I can see "View episode."
		And I can see field "File size" contains "1.04M"
		And I can see field "Date recorded" contains current date in format "j F, Y"
		And I can see field "iTunes Episode Number" contains "123"
		And I can see field "iTunes Episode Title" contains "234"
		And I can see field "iTunes Season Number" contains "345"
		And I can see field "iTunes Episode Type" contains "Full: For Normal Episodes"

		When I click "View episode" link
		Then I can see "Test episode"
		And I can see "Test episode content"
		And I can see "subscribe"
		And I can see "share"
		And I can see "Download file"
		And I can see "Play in new window"
		And I can see "Recorded on"


