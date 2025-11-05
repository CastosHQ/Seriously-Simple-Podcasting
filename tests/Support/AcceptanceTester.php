<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Step\Argument\PasswordArgument;
use Codeception\Util\Locator;
use function PHPUnit\Framework\assertTrue;

/**
 * Acceptance Tester
 *
 * Custom Gherkin step definitions for acceptance tests.
 *
 * @since 3.14.3
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Default episode file URL for testing
     *
     * @since 3.14.3
     */
    const DEFAULT_EPISODE_FILE = 'https://episodes.castos.com/podcasthacker/d21a1b7a-531f-48f1-b4c0-8b8add2bccfe-file-example.mp3';

    /**
     * Login as WordPress admin user
     *
     * Because we test on remote server, standard functions like loginAsAdmin can not be used,
     * so lets rewrite them with user steps.
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function loginAsAdmin(): void
    {
        $this->amOnPage('/wp-login.php');
        $this->see('Username or Email Address');
        $this->fillField('#user_login', new PasswordArgument($_ENV['SITE_USER']));
        $this->fillField('#user_pass', new PasswordArgument($_ENV['SITE_USER_PASS']));
        $this->click('#wp-submit');

        // Fix PhpBrowser bug when it doesn't update the current url and keeps it as /login.php
        $this->amOnPage('/wp-admin/');
    }

    /**
     * Navigate to plugins page
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function amOnPluginsPage(): void
    {
        $this->amOnPage('/wp-admin/plugins.php');
    }

    /**
     * Navigate to URL
     *
     * @When I go to the :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 URL to navigate to.
     * @return void
     */
    public function iGoToThe(string $arg1): void
    {
        $this->amOnPage($arg1);
    }

    /**
     * Verify current URL (not implemented)
     *
     * @When I can see that I am on :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Expected URL.
     * @return void
     * @throws \PHPUnit\Framework\IncompleteTestError
     */
    public function iCanSeeThatIAmOn(string $arg1): void
    {
        throw new \PHPUnit\Framework\IncompleteTestError('Step `I can see that I am on :arg1` is not defined');
    }

    // Gherkin functions.

    /**
     * Navigate to plugins page
     *
     * @Given I am on the plugins page
     * @Given I go to the plugins page
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iAmOnThePluginsPage(): void
    {
        $this->amOnPluginsPage();
    }

    /**
     * Navigate to homepage
     *
     * @When I am on the homepage
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iAmOnTheHomepage(): void
    {
        $this->amOnPage('/');
    }

    /**
     * Login as admin
     *
     * @When I login as admin
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iLoginAsAdmin(): void
    {
        $this->loginAsAdmin();
    }

    /**
     * Verify text is visible
     *
     * @Then I can see :arg1
     * @Then I can see :arg1 text
     *
     * @since 3.14.3
     *
     * @param string $arg1 Text to verify.
     * @return void
     */
    public function iCanSee(string $arg1): void
    {
        $this->see($arg1);
    }

    /**
     * Verify checkbox is checked by label
     *
     * @Then I can see that checkbox for :arg1 label is checked
     *
     * @since 3.14.3
     *
     * @param string $arg1 Checkbox label text.
     * @return void
     */
    public function iCanSeeThatCheckboxForLabelIsChecked(string $arg1): void
    {
        $xpath = "//th[text()='$arg1']/../td/input";
        $this->seeCheckboxIsChecked(['xpath' => $xpath]);
    }

    /**
     * Verify checkbox is not checked by label
     *
     * @Then I don't see that checkbox for :arg1 label is checked
     * @Then I can not see that checkbox for :arg1 label is checked
     *
     * @since 3.14.3
     *
     * @param string $arg1 Checkbox label text.
     * @return void
     */
    public function iDontSeeThatCheckboxForLabelIsChecked(string $arg1): void
    {
        $xpath = "//th[text()='$arg1']/../td/input";
        $this->dontSeeCheckboxIsChecked(['xpath' => $xpath]);
    }

    /**
     * Verify text is not visible
     *
     * @Then I can not see :arg1
     * @Then I can not see :arg1 text
     *
     * @since 3.14.3
     *
     * @param string $arg1 Text to verify is not visible.
     * @return void
     */
    public function iCanNotSee(string $arg1): void
    {
        $this->dontSee($arg1);
    }

    /**
     * Verify SSP plugin is deactivated
     *
     * @Given I can see SSP plugin is deactivated
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanSeeSspIsDeactivated(): void
    {
        $this->see('Activate', '#activate-seriously-simple-podcasting');
    }

    /**
     * Verify SST plugin is deactivated
     *
     * @Given I can see SST plugin is deactivated
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanSeeSSTIsDeactivated(): void
    {
        $this->see('Activate', '#activate-seriously-simple-transcripts');
    }

    /**
     * Activate SSP plugin
     *
     * @When I activate the SSP plugin
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iActivateTheSSPPlugin(): void
    {
        $this->click('#activate-seriously-simple-podcasting');
        $this->wait(1);
    }

    /**
     * Activate SST plugin
     *
     * @When I activate the SST plugin
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iActivateTheSSTPlugin(): void
    {
        $this->click('#activate-seriously-simple-transcripts');
        $this->wait(1);
    }

    /**
     * Activate Classic Editor plugin
     *
     * @When I activate the Classic Editor plugin
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iActivateTheClassicEditorPlugin(): void
    {
        $this->click('#activate-seriously-simple-podcasting');
        $this->wait(1);
    }

    /**
     * Verify SSP plugin is activated
     *
     * @Then I can see SSP plugin is activated
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanSeeSSPPIsActivated(): void
    {
        $this->see('Deactivate', '#deactivate-seriously-simple-podcasting');
    }

    /**
     * Deactivate SSP plugin
     *
     * @When I deactivate the SSP plugin
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iDeactivateTheSSPPlugin(): void
    {
        $this->click('#deactivate-seriously-simple-podcasting');
        $this->wait(1);
    }

    /**
     * Verify onboarding wizard is visible
     *
     * @Then I can see the Onboarding Wizard
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanSeeTheOnboardingWizard(): void
    {
        $this->see("Let's get your podcast started");
    }

    /**
     * Verify onboarding wizard is not visible
     *
     * @Then I can not see the Onboarding Wizard
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanNotSeeTheOnboardingWizard(): void
    {
        $this->dontSee("Let's get your podcast started");
    }

    /**
     * Verify current onboarding wizard step
     *
     * @Then I can see that I am on the :arg1 step of onboarding wizard
     *
     * @since 3.14.3
     *
     * @param string $arg1 Step name.
     * @return void
     */
    public function iCanSeeThatIAmOnTheStep(string $arg1): void
    {
        $this->see($arg1, '.ssp-onboarding__step.active');
    }

    /**
     * Click link by text
     *
     * @When I click :arg1 link
     *
     * @since 3.14.3
     *
     * @param string $arg1 Link text.
     * @return void
     */
    public function iClickLink(string $arg1): void
    {
        $this->click($arg1, 'a');
    }

    /**
     * Fill form field using field map
     *
     * @When I fill the :arg1 with :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @param string $arg2 Value to fill.
     * @return void
     */
    public function iFillTheFieldWith(string $arg1, string $arg2): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg1, $map));
        $this->fillField($map[$arg1], $arg2);
    }

    /**
     * Click button by text
     *
     * @When I click :arg1 button
     *
     * @since 3.14.3
     *
     * @param string $arg1 Button text.
     * @return void
     */
    public function iClickButton(string $arg1): void
    {
        $this->click($arg1, 'button');
    }

    /**
     * Save plugin settings
     *
     * @When I save settings
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iSaveSettings(): void
    {
        $this->click('#ssp-settings-submit');
    }

    /**
     * Select option in dropdown using field map
     *
     * @When I select the :arg1 as :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @param string $arg2 Option value to select.
     * @return void
     */
    public function iSelectTheFieldOption(string $arg1, string $arg2): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg1, $map));
        $this->selectOption($map[$arg1], $arg2);
    }

    /**
     * Get field map for form fields
     *
     * @since 3.14.3
     *
     * @return array<string, string> Field name to selector mapping.
     */
    public function getFieldsMap(): array
    {
        return [
            'Show name'                         => '#show_name',
            'Show description'                  => '#show_description',
            'Primary Category'                  => '#data_category',
            'Primary Sub-Category'              => '#data_subcategory',
            'Feed details Title'                => '#data_title',
            'Feed details Description/Summary'  => '#data_description',
            'Feed details Primary Category'     => '#data_category',
            'Feed details Primary Sub-Category' => '#data_subcategory',
            'Podcast post types Posts'          => '#use_post_types_post',
            'Posts menu'                        => '#menu-posts ul.wp-submenu > li',
            'Episode title'                     => '#title',
            'Episode content'                   => '#content',
            'Episode file'                      => '#upload_audio_file',
            'File size'                         => '#filesize',
            'Date recorded'                     => '#date_recorded_display',
            'Enable iTunes fields'              => '#itunes_fields_enabled',
            'iTunes Episode Number'             => '#itunes_episode_number',
            'iTunes Episode Title'              => '#itunes_title',
            'iTunes Season Number'              => '#itunes_season_number',
            'iTunes Episode Type'               => '#itunes_episode_type',
        ];
    }

    /**
     * Verify field contains value
     *
     * @Then I can see field :arg1 contains :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @param string $arg2 Expected value.
     * @return void
     */
    public function iCanSeeFieldArgContains(string $arg1, string $arg2): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg1, $map));
        $this->seeInField($map[$arg1], $arg2);
    }

    /**
     * Verify field contains current date in specified format
     *
     * @Then I can see field :arg1 contains current date in format :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @param string $arg2 Date format.
     * @return void
     */
    public function iCanSeeFieldContainsCurrentDateInFormat(string $arg1, string $arg2): void
    {
        $dateStr = date($arg2);
        $this->iCanSeeFieldArgContains($arg1, $dateStr);
    }

    /**
     * Navigate to onboarding wizard step
     *
     * @When I go to step :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Step name or number.
     * @return void
     */
    public function iGoToStepNumber(string $arg1): void
    {
        $this->click($arg1, '.ssp-onboarding__step a');
    }

    /**
     * Verify option is selected in dropdown
     *
     * @Then I can see :arg1 selected as :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Selected option value.
     * @param string $arg2 Field name from field map.
     * @return void
     */
    public function iCanSeeOptionSelectedAs(string $arg1, string $arg2): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg2, $map));
        $this->seeOptionIsSelected($map[$arg2], $arg1);
    }

    /**
     * Click admin menu submenu item
     *
     * @When I click :arg1 submenu :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Main menu name.
     * @param string $arg2 Submenu name.
     * @return void
     */
    public function iClickMenuSubmenu(string $arg1, string $arg2): void
    {
        $this->click($arg2, sprintf('#%s ul li a', $this->getAdminMenuId($arg1)));
        $this->wait(1);
    }

    /**
     * Click settings tab
     *
     * @When I click tab :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Tab name.
     * @return void
     */
    public function iClickTabArg(string $arg1): void
    {
        $this->click($arg1, '#ssp-main-settings a.nav-tab');
    }

    /**
     * Verify settings tab is active
     *
     * @Then I can see that :arg1 tab is active
     *
     * @since 3.14.3
     *
     * @param string $arg1 Tab name.
     * @return void
     */
    public function iCanSeeTabIsActive(string $arg1): void
    {
        $this->see($arg1, '#ssp-main-settings a.nav-tab-active');
    }

    /**
     * Check checkbox using field map
     *
     * @When I check :arg1 checkbox
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @return void
     */
    public function iCheckArgCheckbox(string $arg1): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg1, $map));
        $this->checkOption($map[$arg1]);
    }

    /**
     * Uncheck checkbox using field map
     *
     * @When I uncheck :arg1 checkbox
     *
     * @since 3.14.3
     *
     * @param string $arg1 Field name from field map.
     * @return void
     */
    public function iUncheckArgCheckbox(string $arg1): void
    {
        $map = $this->getFieldsMap();
        assertTrue(array_key_exists($arg1, $map));
        $this->uncheckOption($map[$arg1]);
    }

    /**
     * Check checkbox by label text
     *
     * @When I check checkbox with :arg1 label
     *
     * @since 3.14.3
     *
     * @param string $arg1 Checkbox label text.
     * @return void
     */
    public function iCheckCheckboxWithLabel(string $arg1): void
    {
        $xpath = "//th[text()='$arg1']/../td/input";
        $elementId = $this->grabAttributeFrom(['xpath' => $xpath], 'id');
        $this->checkOption('#' . $elementId);
    }

    /**
     * Uncheck checkbox by label text
     *
     * @When I uncheck checkbox with :arg1 label
     *
     * @since 3.14.3
     *
     * @param string $arg1 Checkbox label text.
     * @return void
     */
    public function iUncheckCheckboxWithLabel(string $arg1): void
    {
        $xpath = "//th[text()='$arg1']/../td/input";
        $elementId = $this->grabAttributeFrom(['xpath' => $xpath], 'id');
        $this->uncheckOption('#' . $elementId);
    }

    /**
     * Verify submenu exists in admin menu
     *
     * @Then I can see that :arg1 submenu exists in :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Submenu name.
     * @param string $arg2 Main menu name.
     * @return void
     */
    public function iCanSeeThatSubmenuExistsInMenu(string $arg1, string $arg2): void
    {
        $this->see($arg1, sprintf('#%s ul.wp-submenu > li', $this->getAdminMenuId($arg2)));
    }

    /**
     * Get admin menu ID from menu title
     *
     * @since 3.14.3
     *
     * @param string $menuTitle Menu title.
     * @return string Menu ID.
     */
    protected function getAdminMenuId(string $menuTitle): string
    {
        $id = 'menu-posts';
        if ('Posts' !== $menuTitle) {
            $id .= '-' . strtolower(str_replace(' ', '-', $menuTitle));
        }
        return $id;
    }

    /**
     * Click admin menu item
     *
     * @When I click admin menu :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Menu name.
     * @return void
     */
    public function iClickAdminMenu(string $arg1): void
    {
        $this->click($arg1, '#adminmenu > li');
    }

    /**
     * Verify discount widget is visible
     *
     * @Then I can see that discount widget exists
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iCanSeeThatDiscountWidgetExists(): void
    {
        $this->see('Castos Hosting Discount');
        // WordPress uses curly apostrophe (U+2019), not straight apostrophe (U+0027)
        $this->see("Drop in your name and email and we’ll send you a coupon");
        $this->see('Spam sucks. We will not use your email for anything else');
    }

    /**
     * Test step description
     *
     * @Given I want to :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Step description.
     * @return void
     */
    public function iWantTo(string $arg1): void
    {
        $this->wantTo($arg1);
    }

    /**
     * Verify extension link exists
     *
     * @Then I can see link with title :arg1 and url :arg2
     *
     * @since 3.14.3
     *
     * @param string $arg1 Link title.
     * @param string $arg2 Link URL.
     * @return void
     */
    public function iCanSeeExtensionLink(string $arg1, string $arg2): void
    {
        if (!$this->isAbsoluteUrl($arg2)) {
            $baseUrl = $this->getConfig('url');
            $arg2 = $baseUrl . $arg2;
        }
        $this->see($arg1, Locator::href($arg2));
    }

    /**
     * Save/publish episode
     *
     * @When I save the episode
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function iSaveTheEpisode(): void
    {
        $this->click('#publish');
    }

    /**
     * Create multiple episodes from table
     *
     * @Given I create episodes
     *
     * @since 3.14.3
     *
     * @param \Behat\Gherkin\Node\TableNode $args Episode data table.
     * @return void
     */
    public function iCreateEpisodes($args): void
    {
        $episodes = $args->getTable();
        // Remove titles.
        array_shift($episodes);
        foreach ($episodes as $episode) {
            $this->createEpisode($episode);
        }
    }

    /**
     * Verify current URL
     *
     * @Then I can see that current url is :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Expected URL.
     * @return void
     */
    public function iCanSeeThatCurrentUrlIs(string $arg1): void
    {
        $this->seeInCurrentUrl($arg1);
    }

    /**
     * Check if URL is absolute
     *
     * @since 3.14.3
     *
     * @param string $url URL to check.
     * @return bool True if absolute URL.
     */
    protected function isAbsoluteUrl(string $url): bool
    {
        $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";
        return (bool) preg_match($pattern, $url);
    }

    /**
     * Create a single episode
     *
     * @since 3.14.3
     *
     * @param array<string> $args Episode data [title, content, file_url].
     * @return void
     */
    protected function createEpisode(array $args): void
    {
        $this->iClickMenuSubmenu('Podcast', 'Add New Episode');
        $this->iFillTheFieldWith('Episode title', $args[0]);
        $this->iFillTheFieldWith('Episode content', $args[1]);
        $file = $args[2] ?? self::DEFAULT_EPISODE_FILE;
        $this->iFillTheFieldWith('Episode file', $file);
        $this->iSaveTheEpisode();
    }

    /**
     * Verify content in HTML source
     *
     * @Then I can see in source :arg1
     *
     * @since 3.14.3
     *
     * @param string $arg1 Content to verify (supports {{base_url}}, {{base_url_without_port}}, {{podcast_guid}}).
     * @return void
     */
    public function iCanSeeInSource(string $arg1): void
    {
        $arg1 = str_replace('\"', '"', $arg1);
        if (false !== strpos($arg1, '{{base_url}}')) {
            $arg1 = str_replace('{{base_url}}', $this->getConfig('url'), $arg1);
        } elseif (false !== strpos($arg1, '{{base_url_without_port}}')) {
            $parts = parse_url($this->getConfig('url'));
            $url = $parts['scheme'] . '://' . $parts['host'];
            $arg1 = str_replace('{{base_url_without_port}}', $url, $arg1);
        } elseif (false !== strpos($arg1, '{{podcast_guid}}')) {
            $arg1 = str_replace('{{podcast_guid}}', $this->getConfig('podcastGuid'), $arg1);
        }
        $this->seeInSource($arg1);
    }

}
