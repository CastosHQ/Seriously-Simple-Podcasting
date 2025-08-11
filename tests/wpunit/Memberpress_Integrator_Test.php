<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator;

class Memberpress_Integrator_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator::get_memberships()
	 */
	public function test_get_memberships() {

		$integrator = new \ReflectionClass( '\SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator' );

		$method = $integrator->getMethod( 'get_memberships' );
		$method->setAccessible( true );
		$instance    = Memberpress_Integrator::instance();
		$testing_arg = new stdClass();

		$testing_variants = array(
			array(
				'arg'      => '123,456',
				'expected' => array( 123, 456 ),
			),
			array(
				'arg'      => '',
				'expected' => array(),
			),
			array(
				'arg'      => '123',
				'expected' => array( 123 ),
			),
			array(
				'arg'      => '123, hello',
				'expected' => array( 123 ),
			),
		);

		foreach ( $testing_variants as $testing_variant ) {
			$testing_arg->memberships = $testing_variant['arg'];
			$res                      = $method->invokeArgs( $instance, array( $testing_arg ) );
			self::assertEquals( $testing_variant['expected'], $res );
		}

		$res = $method->invokeArgs( $instance, array( null ) );
		self::assertEquals( array(), $res );
	}
}
