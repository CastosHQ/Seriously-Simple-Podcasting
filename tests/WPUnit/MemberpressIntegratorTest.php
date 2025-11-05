<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator;

class MemberpressIntegratorTest extends \Codeception\TestCase\WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator::get_memberships()
     */
    public function testGetMemberships()
    {
        $integrator = new \ReflectionClass(Memberpress_Integrator::class);

        $method = $integrator->getMethod('get_memberships');
        $method->setAccessible(true);
        $instance = Memberpress_Integrator::instance();
        $testing_arg = new \stdClass();

        $testing_variants = [
            [
                'arg'      => '123,456',
                'expected' => [123, 456],
            ],
            [
                'arg'      => '',
                'expected' => [],
            ],
            [
                'arg'      => '123',
                'expected' => [123],
            ],
            [
                'arg'      => '123, hello',
                'expected' => [123],
            ],
        ];

        foreach ($testing_variants as $testing_variant) {
            $testing_arg->memberships = $testing_variant['arg'];
            $res = $method->invokeArgs($instance, [$testing_arg]);
            $this->assertEquals($testing_variant['expected'], $res);
        }

        $res = $method->invokeArgs($instance, [null]);
        $this->assertEquals([], $res);
    }
}
