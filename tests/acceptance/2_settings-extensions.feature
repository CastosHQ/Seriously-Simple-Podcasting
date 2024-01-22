Feature: SSP Plugin Extensions Settings
	In order to extend the SSP plugin functionality
	As an admin user
	I need to be able to see all available extensions

	Background:
		Given I login as admin
		And I click "Podcast" submenu "Settings"
		And I can see that "General" tab is active
		And I click tab "Extensions"

	Scenario: All extensions exist
		Then I can see that "Extensions" tab is active
		And I can see "These extensions add functionality to your Seriously Simple Podcasting powered podcast."

		And I can see "Castos Podcast Hosting"
		And I can see "Host your podcast media files safely and securely in a CDN"
		And I can see link with title "Get Castos Hosting" and url "https://app.castos.com/"

		And I can see "Seriously Simple Podcasting Stats"
		And I can see "Seriously Simple Stats offers integrated analytics for your podcast"
		And I can see link with title "Get this Extension" and url "/wp-admin/plugin-install.php?tab=plugin-information&plugin=seriously-simple-stats&TB_iframe=true&width=772&height=859"

		And I can see "Seriously Simple Podcasting Transcripts"
		And I can see "Seriously Simple Transcripts gives you a simple and automated way for you to add downloadable"
		And I can see link with title "Get this Extension" and url "/wp-admin/plugin-install.php?tab=plugin-information&plugin=seriously-simple-transcripts&TB_iframe=true&width=772&height=859"

		And I can see "Seriously Simple Podcasting Speakers"
		And I can see "Does your podcast have a number of different speakers? Or maybe a different guest each week?"
		And I can see link with title "Get this Extension" and url "/wp-admin/plugin-install.php?tab=plugin-information&plugin=seriously-simple-speakers&TB_iframe=true&width=772&height=859"

		And I can see "Seriously Simple Podcasting Genesis Support"
		And I can see "The Genesis compatibility add-on for Seriously Simple Podcasting gives you full support"
		And I can see link with title "Get this Extension" and url "/wp-admin/plugin-install.php?tab=plugin-information&plugin=seriously-simple-podcasting-genesis-support&TB_iframe=true&width=772&height=859"

		And I can see that discount widget exists
