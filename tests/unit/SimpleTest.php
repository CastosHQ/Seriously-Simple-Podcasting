<?php

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
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

