<?php
/**
 * WordPress test configuration file.
 *
 * @package Seriously_Simple_Podcasting
 */

// Test database settings
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'wordpress' );
define( 'DB_HOST', 'database' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Test site settings
define( 'WP_TESTS_DOMAIN', 'ssp.lndo.site' );
define( 'WP_TESTS_EMAIL', 'admin@ssp.lndo.site' );
define( 'WP_TESTS_TITLE', 'SSP Test Site' );
define( 'WP_PHP_BINARY', 'php' );

// WordPress table prefix
$table_prefix = 'ssp_test_';

// WordPress debug settings for tests
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Disable file editing
define( 'DISALLOW_FILE_EDIT', true );

// Memory limit
define( 'WP_MEMORY_LIMIT', '256M' );

// Force known bugs for testing
define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

// Multisite settings (if needed)
// define( 'WP_TESTS_MULTISITE', false );

// Skip install if already installed
define( 'WP_TESTS_SKIP_INSTALL', false );





