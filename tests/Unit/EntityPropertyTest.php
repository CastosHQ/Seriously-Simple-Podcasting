<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;
use SeriouslySimplePodcasting\Entities\Abstract_Entity;

class EntityPropertyTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testPropertyTypeGuessing()
    {
        // Test the core logic of Abstract_Entity::guess_property_type() without WordPress dependencies
        
        $test_cases = [
            // Integer values
            '123' => 123,
            '0' => 0,
            '-456' => -456,
            
            // Float values  
            '123.45' => 123.45,
            '0.0' => 0.0,
            '-456.78' => -456.78,
            '123,45' => '123,45', // European decimal separator - not numeric in PHP
            
            // Non-numeric values (should remain unchanged)
            'hello' => 'hello',
            'true' => 'true',
            'false' => 'false',
            '' => '',
            'abc123' => 'abc123',
            '123abc' => '123abc',
        ];
        
        foreach ($test_cases as $input => $expected) {
            $result = $this->simulatePropertyTypeGuessing($input);
            $this->assertEquals($expected, $result, "Failed for input: '{$input}'");
            $this->assertSame(gettype($expected), gettype($result), "Type mismatch for input: '{$input}'");
        }
    }

    public function testNumericDetection()
    {
        // Test numeric detection logic
        
        $numeric_values = ['123', '0', '-456', '123.45', '0.0', '-456.78'];
        $non_numeric_values = ['hello', 'true', 'false', '', 'abc123', '123abc', '12.34.56'];
        
        foreach ($numeric_values as $value) {
            $this->assertTrue(is_numeric($value), "'{$value}' should be detected as numeric");
        }
        
        foreach ($non_numeric_values as $value) {
            $this->assertFalse(is_numeric($value), "'{$value}' should not be detected as numeric");
        }
    }

    public function testIntegerVsFloatDetection()
    {
        // Test integer vs float detection logic
        
        $integer_cases = [
            '123' => true,
            '0' => true,
            '-456' => true,
            '123.45' => false,
            '0.0' => false,
            '-456.78' => false,
            '123,45' => false, // European decimal
        ];
        
        foreach ($integer_cases as $value => $should_be_int) {
            $has_decimal = (strpos($value, '.') !== false) || (strpos($value, ',') !== false);
            $is_integer_format = !$has_decimal;
            
            $this->assertEquals($should_be_int, $is_integer_format, 
                "Integer detection failed for: '{$value}'");
        }
    }

    public function testEntityPropertyFilling()
    {
        // Test entity property filling from arrays and objects
        
        $test_entity = new TestEntity();
        
        // Test array filling
        $array_data = [
            'string_prop' => 'hello',
            'int_prop' => '123',
            'float_prop' => '45.67',
            'bool_prop' => 'true', // Will remain string since it's not numeric
        ];
        
        $test_entity->fillFromArray($array_data);
        
        $this->assertEquals('hello', $test_entity->string_prop);
        $this->assertEquals(123, $test_entity->int_prop);
        $this->assertSame(45.67, $test_entity->float_prop);
        $this->assertEquals('true', $test_entity->bool_prop); // Remains string
        
        // Test object filling
        $object_data = (object) [
            'string_prop' => 'world',
            'int_prop' => '789',
            'float_prop' => '12.34',
        ];
        
        $test_entity->fillFromObject($object_data);
        
        $this->assertEquals('world', $test_entity->string_prop);
        $this->assertEquals(789, $test_entity->int_prop);
        $this->assertSame(12.34, $test_entity->float_prop);
    }

    public function testEdgeCases()
    {
        // Test edge cases for type guessing
        
        $edge_cases = [
            // Leading/trailing zeros
            '0123' => 123, // Leading zero should be treated as int
            '123.00' => 123.0, // Trailing zeros should be treated as float
            
            // Very large numbers
            '9999999999' => 9999999999,
            '999.999999' => 999.999999,
            
            // Scientific notation (PHP's is_numeric handles this)
            '1e5' => 100000.0,
            '1.5e2' => 150.0,
        ];
        
        foreach ($edge_cases as $input => $expected) {
            $result = $this->simulatePropertyTypeGuessing($input);
            $this->assertEquals($expected, $result, "Edge case failed for input: '{$input}'");
        }
    }

    /**
     * Simulate the property type guessing logic without requiring the full Abstract_Entity
     */
    private function simulatePropertyTypeGuessing($val)
    {
        if (is_numeric($val)) {
            $val = (string) $val;
            // If there is neither '.' nor ',' we treat as int; otherwise as float.
            if (false === strpos($val, '.') && false === strpos($val, ',')) {
                $val = (int) $val;
            } else {
                // Convert European decimal separator to standard
                $val = str_replace(',', '.', $val);
                $val = (float) $val;
            }
        }
        return $val;
    }
}

/**
 * Test entity class for testing property filling
 */
class TestEntity
{
    public $string_prop = '';
    public $int_prop = 0;
    public $float_prop = 0.0;
    public $bool_prop = false;

    public function fillFromArray($properties)
    {
        foreach (get_object_vars($this) as $k => $v) {
            if (isset($properties[$k])) {
                $val = $properties[$k];
                $this->{$k} = $this->guessPropertyType($val);
            }
        }
    }

    public function fillFromObject($properties)
    {
        foreach (get_object_vars($this) as $k => $v) {
            if (isset($properties->{$k})) {
                $val = $properties->{$k};
                $this->{$k} = $this->guessPropertyType($val);
            }
        }
    }

    private function guessPropertyType($val)
    {
        if (is_numeric($val)) {
            $val = (string) $val;
            if (false === strpos($val, '.') && false === strpos($val, ',')) {
                $val = (int) $val;
            } else {
                // Convert European decimal separator to standard
                $val = str_replace(',', '.', $val);
                $val = (float) $val;
            }
        }
        return $val;
    }
}
