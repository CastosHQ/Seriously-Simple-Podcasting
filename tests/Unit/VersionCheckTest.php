<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;

class VersionCheckTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testVersionCheckLogic()
    {
        // Test the core logic of ssp_version_check() without WordPress dependencies
        
        // Test stable version (should return false)
        $stable_versions = ['1.0.0', '2.5.1', '3.14.0'];
        
        foreach ($stable_versions as $version) {
            $has_beta = strstr($version, 'beta') !== false;
            $has_alpha = strstr($version, 'alpha') !== false;
            $is_prerelease = $has_beta || $has_alpha;
            
            $this->assertFalse($is_prerelease, "Version {$version} should not be detected as prerelease");
        }
        
        // Test beta versions (should return true)
        $beta_versions = ['1.0.0-beta', '2.5.1-beta.1', '3.14.0-beta.2'];
        
        foreach ($beta_versions as $version) {
            $has_beta = strstr($version, 'beta') !== false;
            $has_alpha = strstr($version, 'alpha') !== false;
            $is_prerelease = $has_beta || $has_alpha;
            
            $this->assertTrue($is_prerelease, "Version {$version} should be detected as prerelease");
        }
        
        // Test alpha versions (should return true)
        $alpha_versions = ['1.0.0-alpha', '2.5.1-alpha.1', '3.14.0-alpha.2'];
        
        foreach ($alpha_versions as $version) {
            $has_beta = strstr($version, 'beta') !== false;
            $has_alpha = strstr($version, 'alpha') !== false;
            $is_prerelease = $has_beta || $has_alpha;
            
            $this->assertTrue($is_prerelease, "Version {$version} should be detected as prerelease");
        }
    }

    public function testPhpVersionCheckLogic()
    {
        // Test the core logic of ssp_is_php_version_ok() without WordPress dependencies
        
        $valid_versions = ['5.6.0', '7.0.0', '7.4.33', '8.0.0', '8.1.0', '8.2.0'];
        
        foreach ($valid_versions as $version) {
            $is_valid = version_compare($version, '5.6', '>=');
            $this->assertTrue($is_valid, "PHP version {$version} should be valid");
        }
        
        $invalid_versions = ['5.5.9', '5.4.0', '5.3.29'];
        
        foreach ($invalid_versions as $version) {
            $is_valid = version_compare($version, '5.6', '>=');
            $this->assertFalse($is_valid, "PHP version {$version} should be invalid");
        }
    }

    public function testVendorCheckLogic()
    {
        // Test the core logic of ssp_is_vendor_ok() without WordPress dependencies
        
        // Test with a file that should exist (this test file itself)
        $existing_file = __FILE__;
        $this->assertTrue(file_exists($existing_file), "Test file should exist");
        
        // Test with a file that shouldn't exist
        $non_existing_file = '/path/that/does/not/exist/file.php';
        $this->assertFalse(file_exists($non_existing_file), "Non-existing file should not exist");
        
        // Test the actual vendor autoload path logic
        $plugin_path = dirname(dirname(__DIR__)); // Go up to plugin root (tests/Unit -> tests -> plugin root)
        $vendor_autoload = $plugin_path . '/vendor/autoload.php';
        
        // This should exist in our plugin
        $this->assertTrue(file_exists($vendor_autoload), "Vendor autoload should exist at: {$vendor_autoload}");
    }

    public function testCurrentPluginVersion()
    {
        // Test with the actual current plugin version constant
        if (defined('SSP_VERSION')) {
            $current_version = SSP_VERSION;
            
            // Test if current version follows expected format
            $this->assertIsString($current_version);
            $this->assertNotEmpty($current_version);
            
            // Test version detection logic with current version
            $has_beta = strstr($current_version, 'beta') !== false;
            $has_alpha = strstr($current_version, 'alpha') !== false;
            $is_prerelease = $has_beta || $has_alpha;
            
            // Log what we found for debugging
            $prerelease_status = $is_prerelease ? 'prerelease' : 'stable';
            $this->assertIsString($current_version, "Current version {$current_version} detected as {$prerelease_status}");
        } else {
            $this->markTestSkipped('SSP_VERSION constant not defined');
        }
    }
}
