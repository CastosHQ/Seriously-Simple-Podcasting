<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;

class FileSizeTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testConvertHumanReadableToBytes()
    {
        // Test the core logic of convert_human_readable_to_bytes() without WordPress dependencies
        
        $test_cases = [
            // Kilobytes (two-letter suffix)
            '1 KB' => 1024,
            '10KB' => 10240,
            '280 kb' => 286720,
            '1.5KB' => 1536,
            
            // Kilobytes (single-letter suffix from format_bytes)
            '1k' => 1024,
            '10k' => 10240,
            '280K' => 286720,
            
            // Megabytes (two-letter suffix)
            '1 MB' => 1048576,
            '10MB' => 10485760,
            '2.5 mb' => 2621440,
            '100 Mb' => 104857600,
            
            // Megabytes (single-letter suffix from format_bytes)
            '1M' => 1048576,
            '10M' => 10485760,
            '2.5m' => 2621440,
            
            // Gigabytes (two-letter suffix)
            '1 GB' => 1073741824,
            '2GB' => 2147483648,
            '1.5 gb' => 1610612736,
            
            // Gigabytes (single-letter suffix from format_bytes)
            '1G' => 1073741824,
            '2g' => 2147483648,
            
            // Terabytes (two-letter suffix)
            '1 TB' => 1099511627776,
            '2TB' => 2199023255552,
            
            // Terabytes (single-letter suffix from format_bytes)
            '1T' => 1099511627776,
            '2t' => 2199023255552,
            
            // Petabytes (two-letter suffix)
            '1 PB' => 1125899906842624,
            
            // Petabytes (single-letter suffix from format_bytes)
            '1P' => 1125899906842624,
            
            // No unit (bytes)
            '1024' => 1024,
            '500' => 500,
            '0' => 0,
            
            // Edge cases with spaces
            ' 5 MB ' => 5242880,
            '10 KB' => 10240,
        ];
        
        foreach ($test_cases as $input => $expected) {
            $result = $this->simulateHumanReadableToBytes($input);
            $this->assertEquals($expected, $result, "Failed for input: '{$input}'");
        }
    }

    public function testFileSizeUnitExtraction()
    {
        // Test unit extraction logic
        
        $unit_cases = [
            '280 kb' => 'kb',
            '10MB' => 'MB',
            '1.5 GB' => 'GB',
            '2TB' => 'TB',
            '1PB' => 'PB',
            '1024' => '',
            ' 5 MB ' => 'MB',
        ];
        
        foreach ($unit_cases as $input => $expected_unit) {
            $extracted_unit = preg_replace('/[^a-z]/i', '', $input);
            $this->assertEquals($expected_unit, $extracted_unit, "Unit extraction failed for: '{$input}'");
        }
    }

    public function testFileSizeValueExtraction()
    {
        // Test value extraction logic
        
        $value_cases = [
            '280 kb' => '280',
            '10MB' => '10',
            '1.5 GB' => '1.5',
            '2TB' => '2',
            '1024' => '1024',
            ' 5 MB ' => '5',
        ];
        
        foreach ($value_cases as $input => $expected_value) {
            $unit = preg_replace('/[^a-z]/i', '', $input);
            $extracted_value = trim(str_replace($unit, '', $input));
            $this->assertEquals($expected_value, $extracted_value, "Value extraction failed for: '{$input}'");
        }
    }

    public function testCaseInsensitivity()
    {
        // Test that units are case insensitive
        
        $case_variants = [
            '1 kb' => 1024,
            '1 KB' => 1024,
            '1 Kb' => 1024,
            '1 kB' => 1024,
            '1 mb' => 1048576,
            '1 MB' => 1048576,
            '1 Mb' => 1048576,
            '1 mB' => 1048576,
        ];
        
        foreach ($case_variants as $input => $expected) {
            $result = $this->simulateHumanReadableToBytes($input);
            $this->assertEquals($expected, $result, "Case sensitivity failed for: '{$input}'");
        }
    }

    public function testDecimalValues()
    {
        // Test decimal values in file sizes
        
        $decimal_cases = [
            '1.5 KB' => 1536,      // 1.5 * 1024
            '2.5 MB' => 2621440,   // 2.5 * 1024^2
            '0.5 GB' => 536870912, // 0.5 * 1024^3
            '10.25 KB' => 10496,   // 10.25 * 1024
        ];
        
        foreach ($decimal_cases as $input => $expected) {
            $result = $this->simulateHumanReadableToBytes($input);
            $this->assertEquals($expected, $result, "Decimal value failed for: '{$input}'");
            $this->assertIsInt($result, "Result should be integer for: '{$input}'");
        }
    }

    public function testReturnsIntegerNotFloat()
    {
        // Ensure function returns integers, not floats
        
        $test_cases = [
            '1.5 KB',
            '2.5 MB',
            '10M',
            '5G',
            '1024',
        ];
        
        foreach ($test_cases as $input) {
            $result = $this->simulateHumanReadableToBytes($input);
            $this->assertIsInt($result, "Result must be integer for: '{$input}', got: " . gettype($result));
        }
    }

    public function testFormatBytesReverse()
    {
        // Test the reverse operation (bytes to human readable) from Episode_Repository
        
        $byte_cases = [
            1024 => ['1', 'k'],
            1048576 => ['1', 'M'], 
            1073741824 => ['1', 'G'],
            2048 => ['2', 'k'],
            5242880 => ['5', 'M'],
        ];
        
        foreach ($byte_cases as $bytes => $expected) {
            $result = $this->simulateFormatBytes($bytes, 0); // 0 precision for clean results
            $this->assertStringContainsString($expected[0], $result, "Format bytes failed for: {$bytes}");
            $this->assertStringContainsString($expected[1], $result, "Format bytes unit failed for: {$bytes}");
        }
    }

    public function testLargeFileSizes()
    {
        // Test very large file sizes
        
        $large_cases = [
            '1000 GB' => 1073741824000,
            '5 TB' => 5497558138880,
            '10 PB' => 11258999068426240,
        ];
        
        foreach ($large_cases as $input => $expected) {
            $result = $this->simulateHumanReadableToBytes($input);
            $this->assertEquals($expected, $result, "Large file size failed for: '{$input}'");
        }
    }

    /**
     * Simulate the human readable to bytes conversion without WordPress dependencies
     */
    private function simulateHumanReadableToBytes($formatted_size)
    {
        $formatted_size_type = preg_replace('/[^a-z]/i', '', $formatted_size);
        $formatted_size_value = trim(str_replace($formatted_size_type, '', $formatted_size));

        switch (strtoupper($formatted_size_type)) {
            case 'K':   // Single letter (from format_bytes).
            case 'KB':  // Two letters (standard).
                return (int) ( $formatted_size_value * 1024 );
            case 'M':   // Single letter (from format_bytes).
            case 'MB':  // Two letters (standard).
                return (int) ( $formatted_size_value * pow(1024, 2) );
            case 'G':   // Single letter (from format_bytes).
            case 'GB':  // Two letters (standard).
                return (int) ( $formatted_size_value * pow(1024, 3) );
            case 'T':   // Single letter (from format_bytes).
            case 'TB':  // Two letters (standard).
                return (int) ( $formatted_size_value * pow(1024, 4) );
            case 'P':   // Single letter (from format_bytes).
            case 'PB':  // Two letters (standard).
                return (int) ( $formatted_size_value * pow(1024, 5) );
            default:
                return (int) $formatted_size_value;
        }
    }

    /**
     * Simulate the format bytes function (reverse operation)
     */
    private function simulateFormatBytes($size, $precision = 2)
    {
        if ($size) {
            $base = log($size) / log(1024);
            $suffixes = ['', 'k', 'M', 'G', 'T'];
            $formatted_size = round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
            return $formatted_size;
        }
        return false;
    }
}





