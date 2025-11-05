<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;

class BasicPluginTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testBasicAssertion()
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertNotEmpty('hello world');
    }

    public function testPhpFunctions()
    {
        $this->assertEquals('hello world', strtolower('HELLO WORLD'));
        $this->assertEquals(11, strlen('hello world'));
        $this->assertTrue(is_array([]));
    }

    public function testComposerAutoloader()
    {
        $this->assertTrue(class_exists('Codeception\Test\Unit'));
        $this->assertTrue(class_exists('Tests\Support\UnitTester'));
    }

    public function testArrayFunctions()
    {
        $array = ['apple', 'banana', 'cherry'];
        $this->assertCount(3, $array);
        $this->assertContains('banana', $array);
        $this->assertEquals('apple', $array[0]);
    }
}
