<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

/**
 * Player color and opacity values are written to the generated CSS only when they have a valid format.
 */
class SettingsControllerPlayerCssTest extends \Codeception\TestCase\WPTestCase {

	const COLOR_ID  = 'player_progress_bar_color';
	const COLOR_VAR = '--ssp-progress-bar-color';

	const OPACITY_ID  = 'player_btns_opacity';
	const OPACITY_VAR = '--ssp-player-btns-opacity';

	const EXTRA_ID  = 'player_extra_setting';
	const EXTRA_VAR = '--ssp-extra-setting';

	/**
	 * @var Settings_Controller
	 */
	private $settings_controller;

	protected function setUp(): void {
		parent::setUp();

		$this->settings_controller = new Settings_Controller(
			new Settings_Handler(),
			Settings_Renderer::instance(),
			new Renderer(),
			$this->createMock( Series_Handler::class ),
			$this->createMock( Castos_Handler::class ),
			$this->createMock( Episode_Repository::class ),
			new Sync_Refusal_Repository()
		);
	}

	protected function tearDown(): void {
		delete_option( Settings_Controller::SETTINGS_BASE . self::COLOR_ID );
		delete_option( Settings_Controller::SETTINGS_BASE . self::OPACITY_ID );
		delete_option( Settings_Controller::SETTINGS_BASE . self::EXTRA_ID );
		remove_all_filters( 'ssp_player_color_settings' );
		parent::tearDown();
	}

	public function valid_color_provider() {
		return array(
			'short hex'        => array( '#fff' ),
			'short hex alpha'  => array( '#FFFA' ),
			'hex'              => array( '#24212c' ),
			'hex alpha'        => array( '#24212c80' ),
			'rgb'              => array( 'rgb(1,2,3)' ),
			'rgba'             => array( 'rgba(1, 2, 3, 0.5)' ),
			'rgb space syntax' => array( 'rgb(1 2 3 / 50%)' ),
			'rgb percentages'  => array( 'rgb(1%,2%,3%)' ),
			'rgb fraction'     => array( 'rgb(.5,2,3)' ),
		);
	}

	public function invalid_color_provider() {
		return array(
			'named color'                => array( 'red' ),
			'two digit hex'              => array( '#ff' ),
			'five digit hex'             => array( '#fffff' ),
			'hex with trailing text'     => array( '#fff;x' ),
			'rgb with trailing text'     => array( 'rgb(1,2,3)x' ),
			'unsupported color function' => array( 'hsl(0, 0%, 0%)' ),
			'leading text'               => array( 'x#fff' ),
			'mixed comma channel units'  => array( 'rgb(1%,2,3)' ),
			'opacity value'              => array( '0.4' ),
		);
	}

	/**
	 * @dataProvider valid_color_provider
	 */
	public function test_valid_color_is_written_unchanged( $value ) {
		update_option( Settings_Controller::SETTINGS_BASE . self::COLOR_ID, $value );

		$this->assertStringContainsString( self::COLOR_VAR . ':' . $value . ';', $this->generate_css() );
	}

	/**
	 * @dataProvider invalid_color_provider
	 */
	public function test_invalid_color_is_skipped( $value ) {
		update_option( Settings_Controller::SETTINGS_BASE . self::COLOR_ID, $value );

		$css = $this->generate_css();

		$this->assertStringNotContainsString( self::COLOR_VAR, $css );
		$this->assertStringNotContainsString( ':' . $value . ';', $css );
	}

	public function test_invalid_color_does_not_stop_other_settings_from_being_written() {
		update_option( Settings_Controller::SETTINGS_BASE . self::COLOR_ID, 'red' );

		$this->assertStringContainsString( '--ssp-play-btn-color:#dd4142;', $this->generate_css() );
	}

	public function test_valid_opacity_is_written_unchanged() {
		foreach ( array( '0.5', '0.3', '1', '0', '0.0', 0, 0.0 ) as $value ) {
			update_option( Settings_Controller::SETTINGS_BASE . self::OPACITY_ID, $value );

			$this->assertStringContainsString( self::OPACITY_VAR . ':' . $value . ';', $this->generate_css(), 'Opacity ' . $value );
		}
	}

	public function test_invalid_opacity_is_skipped() {
		foreach ( array( '1.5', '-0.1', '#abc' ) as $value ) {
			update_option( Settings_Controller::SETTINGS_BASE . self::OPACITY_ID, $value );

			$css = $this->generate_css();

			$this->assertStringNotContainsString( self::OPACITY_VAR, $css, 'Opacity ' . $value );
			$this->assertStringNotContainsString( ':' . $value . ';', $css, 'Opacity ' . $value );
		}
	}

	/**
	 * @dataProvider valid_color_provider
	 */
	public function test_valid_value_of_filtered_setting_is_written( $value ) {
		$this->add_extra_setting();
		update_option( Settings_Controller::SETTINGS_BASE . self::EXTRA_ID, $value );

		$this->assertStringContainsString( self::EXTRA_VAR . ':' . $value . ';', $this->generate_css() );
	}

	/**
	 * @dataProvider invalid_color_provider
	 */
	public function test_invalid_value_of_filtered_setting_is_skipped( $value ) {
		$this->add_extra_setting();
		update_option( Settings_Controller::SETTINGS_BASE . self::EXTRA_ID, $value );

		$css = $this->generate_css();

		$this->assertStringNotContainsString( self::EXTRA_VAR, $css );
		$this->assertStringNotContainsString( ':' . $value . ';', $css );
	}

	public function test_filtered_setting_with_other_type_takes_color_or_opacity() {
		$this->add_extra_setting( 'text' );

		foreach ( array( '#fff', '0.5' ) as $value ) {
			update_option( Settings_Controller::SETTINGS_BASE . self::EXTRA_ID, $value );

			$this->assertStringContainsString( self::EXTRA_VAR . ':' . $value . ';', $this->generate_css(), 'Value ' . $value );
		}
	}

	/** Adds a setting through the filter, as an add-on would. */
	private function add_extra_setting( $type = 'color' ) {
		add_filter(
			'ssp_player_color_settings',
			function ( $settings ) use ( $type ) {
				$settings[] = array(
					'id'      => self::EXTRA_ID,
					'type'    => $type,
					'css_var' => self::EXTRA_VAR,
				);

				return $settings;
			}
		);
	}

	private function generate_css() {
		$method = new \ReflectionMethod( $this->settings_controller, 'generate_player_css' );
		$method->setAccessible( true );

		return $method->invoke( $this->settings_controller );
	}
}
