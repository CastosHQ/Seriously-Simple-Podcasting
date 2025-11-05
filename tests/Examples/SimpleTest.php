<?php

namespace Tests\Examples;

use Tests\Support\UnitTester;

class SimpleTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }
    public function testBasicFunctionality()
    {
        $this->assertTrue(true, 'Basic test should pass');
    }

    public function testStringOperations()
    {
        $string = 'Hello World';
        $this->assertEquals('Hello World', $string);
        $this->assertStringContainsString('World', $string);
    }

    public function testArrayOperations()
    {
        $array = [1, 2, 3, 4, 5];
        $this->assertCount(5, $array);
        $this->assertContains(3, $array);
    }
}

