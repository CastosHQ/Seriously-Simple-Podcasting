<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Onboarding_Controller;
use SeriouslySimplePodcasting\Handlers\Onboarding_Import_Handler;

class OnboardingControllerTest extends \Codeception\TestCase\WPTestCase
{
    /** @var Onboarding_Controller */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Onboarding_Controller(
            ssp_get_service('renderer'),
            ssp_get_service('settings_handler')
        );
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    /**
     * Calls a protected controller method.
     */
    private function call($method, $args = [])
    {
        $reflection = new \ReflectionMethod(Onboarding_Controller::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->controller, $args);
    }

    private function create_series($name)
    {
        return $this->factory()->term->create(['taxonomy' => ssp_series_taxonomy(), 'name' => $name]);
    }

    /**
     * Points the wizard at a podcast the way an onboarding import does.
     */
    private function set_target_series($series_id)
    {
        update_option(Onboarding_Import_Handler::TARGET_SERIES_OPTION, $series_id, false);
    }

    /**
     * Without an import the wizard works on the site's default podcast.
     */
    public function testTargetDefaultsToTheDefaultPodcast()
    {
        $default_id = $this->create_series('My Site');
        ssp_update_option('default_series', $default_id);

        $this->assertSame($default_id, $this->call('get_target_series_id'));
        $this->assertFalse($this->call('has_imported_podcast'));
    }

    /**
     * Steps read the imported podcast's settings, not the default podcast's.
     */
    public function testStepDataReadsTheTargetPodcast()
    {
        $default_id  = $this->create_series('My Site');
        $imported_id = $this->create_series('The Audience Show');
        ssp_update_option('default_series', $default_id);

        ssp_update_option('data_image', 'https://example.com/default.jpg', $default_id);
        ssp_update_option('data_image', 'https://example.com/imported.jpg', $imported_id);

        $this->set_target_series($imported_id);
        $data = $this->call('get_step_data', [2]);

        $this->assertSame('https://example.com/imported.jpg', $data['data_image']);
        $this->assertTrue($data['imported']);
    }

    /**
     * Steps write to the imported podcast, leaving the default podcast alone.
     */
    public function testSaveStepWritesToTheTargetPodcast()
    {
        $default_id  = $this->create_series('My Site');
        $imported_id = $this->create_series('The Audience Show');
        ssp_update_option('default_series', $default_id);
        $this->set_target_series($imported_id);

        $_POST = [
            'nonce'      => wp_create_nonce('ssp_onboarding_2'),
            'data_image' => 'https://example.com/new-cover.jpg',
        ];

        $this->call('save_step', [2]);

        $this->assertSame('https://example.com/new-cover.jpg', ssp_get_option('data_image', '', $imported_id));
        $this->assertSame('', ssp_get_option('data_image', '', $default_id));
    }

    /**
     * Step 1's title placeholder goes to the podcast the wizard is working on.
     */
    public function testFeedTitlePlaceholderGoesToTheTargetPodcast()
    {
        $default_id  = $this->create_series('My Site');
        $imported_id = $this->create_series('The Audience Show');
        ssp_update_option('default_series', $default_id);
        $this->set_target_series($imported_id);

        $this->call('maybe_update_feed_title');

        $this->assertNotEmpty(ssp_get_option('data_title', '', $imported_id));
        $this->assertSame('', ssp_get_option('data_title', '', $default_id));
    }

    /**
     * The step track is supplied as data, one label per step.
     */
    public function testStepLabelsCoverEveryStep()
    {
        $labels = $this->call('get_step_labels');

        $this->assertCount(Onboarding_Controller::STEPS_NUMBER, $labels);
        $this->assertSame([1, 2, 3, 4, 5], array_keys($labels));
    }

    /**
     * A target podcast that has been deleted stops counting as an import.
     *
     * get_target_series_id() falls back to the default podcast when the term is
     * gone, so the imported flag has to fall back with it — otherwise the steps
     * read the default podcast while still showing imported-podcast wording.
     */
    public function testDeletedTargetStopsCountingAsImported()
    {
        $default_id  = $this->create_series('My Site');
        $imported_id = $this->create_series('The Audience Show');
        ssp_update_option('default_series', $default_id);
        $this->set_target_series($imported_id);

        $this->assertTrue($this->call('has_imported_podcast'));

        wp_delete_term($imported_id, ssp_series_taxonomy());

        $this->assertSame($default_id, $this->call('get_target_series_id'));
        $this->assertFalse(
            $this->call('has_imported_podcast'),
            'A target that no longer resolves must not leave the wizard in the imported state'
        );
    }

    /**
     * Renders step 4 with the given stored API token and returns the HTML.
     *
     * @param string $token Stored Castos API token.
     *
     * @return string
     */
    private function render_step_4($token)
    {
        $filter = function () use ($token) {
            return $token;
        };
        add_filter('pre_option_ss_podcasting_podmotor_account_api_token', $filter);

        ob_start();
        try {
            $this->controller->step_4();
        } finally {
            $html = ob_get_clean();
            remove_filter('pre_option_ss_podcasting_podmotor_account_api_token', $filter);
        }

        return $html;
    }

    /**
     * Parses an HTML string into a DOM document.
     *
     * @param string $html HTML markup.
     *
     * @return \DOMDocument
     */
    private function parse_html($html)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        return $dom;
    }

    /**
     * A token containing quotes and markup stays inside the input's value.
     */
    public function testTokenFieldEscapesValue()
    {
        $token = 'x" data-x="1" title="<b>y</b>';

        $html = $this->render_step_4($token);

        $this->assertStringNotContainsString('" data-x=', $html);

        $input = $this->parse_html($html)->getElementById('podmotor_account_api_token');
        $this->assertNotNull($input);
        $this->assertSame($token, $input->getAttribute('value'));
        $this->assertFalse($input->hasAttribute('data-x'));
        $this->assertFalse($input->hasAttribute('title'));
    }

    /**
     * An ordinary token is printed as is.
     */
    public function testTokenFieldKeepsOrdinaryValue()
    {
        $html = $this->render_step_4('abc123');

        $this->assertStringContainsString('name="podmotor_account_api_token" value="abc123"', $html);

        $dom   = $this->parse_html($html);
        $input = $dom->getElementById('podmotor_account_api_token');
        $this->assertSame('abc123', $input->getAttribute('value'));

        $form = (new \DOMXPath($dom))->query('//form[contains(@class, "ssp-onboarding__settings-body")]')->item(0);
        $this->assertSame($this->call('get_step_url', [5]), $form->getAttribute('action'));
    }

    /**
     * The form action prints the step URL escaped as a URL.
     */
    public function testFormActionEscapesUrl()
    {
        $filter = function ($url) {
            if (false !== strpos($url, 'ssp-onboarding-5')) {
                return 'https://example.com/wp-admin/admin.php?page=x" data-x="1';
            }

            return $url;
        };
        add_filter('admin_url', $filter);

        try {
            $html = $this->render_step_4('');
        } finally {
            remove_filter('admin_url', $filter);
        }

        $this->assertStringNotContainsString('" data-x=', $html);

        $form = (new \DOMXPath($this->parse_html($html)))->query('//form[contains(@class, "ssp-onboarding__settings-body")]')->item(0);
        $this->assertNotNull($form);
        $this->assertFalse($form->hasAttribute('data-x'));
        $this->assertStringNotContainsString('"', $form->getAttribute('action'));
        $this->assertStringStartsWith('https://example.com/wp-admin/admin.php?page=x', $form->getAttribute('action'));
    }
}
