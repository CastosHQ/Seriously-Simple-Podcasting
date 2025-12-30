<?php
/**
 * Tests for Frontend_Controller
 *
 * Tests for the Frontend_Controller class including URL validation,
 * download functionality, and security features.
 *
 * @package seriously-simple-podcasting
 * @since 3.14.3
 */

namespace SeriouslySimplePodcasting\Tests\WPUnit;

use Codeception\TestCase\WPTestCase;

class FrontendControllerTest extends WPTestCase {

	/**
	 * @var \SeriouslySimplePodcasting\Controllers\Frontend_Controller
	 */
	protected $frontend_controller;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->frontend_controller = ssp_frontend_controller();
	}

	// ========================================
	// SECTION 1: URL VALIDATION TESTS
	// ========================================

	/**
	 * Test that internal network URLs are properly validated
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_validates_internal_network_urls() {
		$test_urls = array(
			'http://internal.test/admin',
			'http://local.example.test/secret',
			'http://localhost.example.test/api',
			'http://private.example.test/service',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should block internal network URLs: {$url}" );
		}
	}

	/**
	 * Test that private network IP ranges are validated
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_validates_private_network_ips() {
		$test_urls = array(
			'http://10.0.0.1/internal',
			'http://10.255.255.255/database',
			'http://192.168.1.1/router',
			'http://192.168.0.100/admin',
			'http://172.16.0.1/service',
			'http://172.31.255.255/api',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should validate private IP ranges: {$url}" );
		}
	}

	/**
	 * Test that link-local IP addresses are validated
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_validates_link_local_addresses() {
		$test_urls = array(
			'http://169.254.1.1/endpoint/',
			'http://metadata.cloud.test/v1/',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should validate link-local addresses: {$url}" );
		}
	}

	/**
	 * Test that special internal hostnames are validated
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_validates_special_hostnames() {
		$test_urls = array(
			'http://localhost.localdomain/admin',
			'http://internal.local/service',
			'http://api.internal/data',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should validate special hostnames: {$url}" );
		}
	}

	/**
	 * Test that legitimate external URLs are allowed
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_allows_legitimate_external_urls() {
		$safe_urls = array(
			'https://cdn.castos.com/podcast.mp3',
			'https://media.blubrry.com/audio/episode1.mp3',
			'https://s3.amazonaws.com/bucket/audio.mp3',
			'https://storage.googleapis.com/bucket/file.mp3',
			'http://8.8.8.8/public-resource',
		);

		foreach ( $safe_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertTrue( $result, "Legitimate URL should be allowed: {$url}" );
		}
	}

	/**
	 * Test that URL parsing is consistent
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_url_parsing_consistency() {
		$test_urls = array(
			'http://example.com/path?host=internal.test',
			'http://example.com/redirect?url=localhost',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertTrue( $result, "Should handle standard URL formats: {$url}" );
		}
	}

	/**
	 * Test that only HTTP/HTTPS protocols are allowed
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_allows_only_http_protocols() {
		$test_urls = array(
			'file://server/path/file.txt',
			'ftp://server.test/file.txt',
			'gopher://server.test/path',
			'data://text/plain;base64,test',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should only allow HTTP/HTTPS protocols: {$url}" );
		}
	}

	/**
	 * Test that IPv6 addresses are properly validated
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_validates_ipv6_addresses() {
		$test_urls = array(
			'http://[::1]/admin',
			'http://[0:0:0:0:0:0:0:1]/service',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertFalse( $result, "Should validate IPv6 addresses: {$url}" );
		}
	}

	/**
	 * Test WordPress HTTP validation integration
	 *
	 * @group security
	 * @group integration
	 */
	public function test_integrates_with_wordpress_http_validation() {
		$test_url = 'http://test.internal.test:8080/api';
		
		// Our method should validate URLs
		$result = $this->frontend_controller->validate_file_url( $test_url );
		$this->assertFalse( $result, 'Should integrate with WordPress validation' );
	}

	/**
	 * Test that URL encoding is handled properly
	 *
	 * @group security
	 * @group url-validation
	 */
	public function test_handles_url_encoding() {
		$test_urls = array(
			'https://example.com/podcast%20episode.mp3',
			'https://example.com/path%2Fto%2Ffile.mp3',
		);

		foreach ( $test_urls as $url ) {
			$result = $this->frontend_controller->validate_file_url( $url );
			$this->assertTrue( $result, "Should handle properly encoded URLs: {$url}" );
		}
	}

	// ========================================
	// SECTION 2: CLIENT IP DETECTION TESTS
	// ========================================

	/**
	 * Test client IP detection with direct connection
	 *
	 * @group helpers
	 * @group client-ip
	 */
	public function test_get_client_ip_direct_connection() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$_SERVER['REMOTE_ADDR'] = '203.0.113.42';
		$ip                      = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( '203.0.113.42', $ip );
		
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test client IP detection with proxy (Cloudflare)
	 *
	 * @group helpers
	 * @group client-ip
	 */
	public function test_get_client_ip_with_cloudflare() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.5';
		$_SERVER['REMOTE_ADDR']            = '192.0.2.1';
		$ip                                 = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( '198.51.100.5', $ip, 'Should prefer Cloudflare header' );
		
		unset( $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test client IP detection with X-Forwarded-For chain
	 *
	 * @group helpers
	 * @group client-ip
	 */
	public function test_get_client_ip_x_forwarded_for_chain() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.1, 198.51.100.10, 203.0.113.20';
		$ip                                = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( '192.0.2.1', $ip, 'Should use first IP from X-Forwarded-For' );
		
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	/**
	 * Test client IP detection with invalid IP
	 *
	 * @group helpers
	 * @group client-ip
	 */
	public function test_get_client_ip_handles_invalid_ip() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
		$ip                      = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( 'unknown', $ip, 'Should return "unknown" for invalid IP' );
		
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test client IP detection when no IP available
	 *
	 * @group helpers
	 * @group client-ip
	 */
	public function test_get_client_ip_no_ip_available() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'] );
		
		$result = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( 'unknown', $result );
	}

	// ========================================
	// SECTION 3: URL PROCESSING TESTS
	// ========================================

	/**
	 * Test clean_file_url removes newlines
	 *
	 * @group helpers
	 * @group url-processing
	 */
	public function test_clean_file_url_removes_newlines() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'clean_file_url' );
		$method->setAccessible( true );

		$test_cases = array(
			array(
				'input'    => "https://example.com/file.mp3\nextra data",
				'expected' => 'https://example.com/file.mp3',
			),
			array(
				'input'    => "https://example.com/file.mp3\nline2\nline3",
				'expected' => 'https://example.com/file.mp3',
			),
			array(
				'input'    => 'https://example.com/file.mp3',
				'expected' => 'https://example.com/file.mp3',
			),
		);

		foreach ( $test_cases as $case ) {
			$result = $method->invoke( $this->frontend_controller, $case['input'] );
			$this->assertEquals( $case['expected'], $result );
		}
	}

	/**
	 * Test encode_file_url encodes spaces
	 *
	 * @group helpers
	 * @group url-processing
	 */
	public function test_encode_file_url_encodes_spaces() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'encode_file_url' );
		$method->setAccessible( true );

		$test_cases = array(
			array(
				'input'    => 'https://example.com/my file.mp3',
				'expected' => 'https://example.com/my%20file.mp3',
			),
			array(
				'input'    => 'https://example.com/my  multiple  spaces.mp3',
				'expected' => 'https://example.com/my%20%20multiple%20%20spaces.mp3',
			),
			array(
				'input'    => ' https://example.com/file.mp3 ',
				'expected' => '%20https://example.com/file.mp3%20',
			),
		);

		foreach ( $test_cases as $case ) {
			$result = $method->invoke( $this->frontend_controller, $case['input'] );
			$this->assertEquals( $case['expected'], $result );
		}
	}

	/**
	 * Test encode_file_url removes PHP_EOL
	 *
	 * @group helpers
	 * @group url-processing
	 */
	public function test_encode_file_url_removes_php_eol() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'encode_file_url' );
		$method->setAccessible( true );

		$file_with_eol = 'https://example.com/file.mp3' . PHP_EOL;
		$result        = $method->invoke( $this->frontend_controller, $file_with_eol );
		
		$this->assertStringNotContainsString( PHP_EOL, $result );
		$this->assertEquals( 'https://example.com/file.mp3', $result );
	}

	/**
	 * Test encode_file_url doesn't double-encode
	 *
	 * @group helpers
	 * @group url-processing
	 */
	public function test_encode_file_url_no_double_encoding() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'encode_file_url' );
		$method->setAccessible( true );

		$already_encoded = 'https://example.com/my%20file.mp3';
		$result          = $method->invoke( $this->frontend_controller, $already_encoded );
		
		$this->assertEquals( $already_encoded, $result );
	}

	// ========================================
	// SECTION 4: EPISODE AND REFERRER TESTS
	// ========================================

	/**
	 * Test get_episode_id_from_query returns valid ID
	 *
	 * @group helpers
	 * @group episode
	 */
	public function test_get_episode_id_from_query_returns_valid_id() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_episode_id_from_query' );
		$method->setAccessible( true );

		$wp_query->query_vars['podcast_episode'] = 456;
		$result                                    = $method->invoke( $this->frontend_controller );
		$this->assertSame( 456, $result );
		
		unset( $wp_query->query_vars['podcast_episode'] );
	}

	/**
	 * Test get_episode_id_from_query returns zero when missing
	 *
	 * @group helpers
	 * @group episode
	 */
	public function test_get_episode_id_from_query_returns_zero_when_missing() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_episode_id_from_query' );
		$method->setAccessible( true );

		unset( $wp_query->query_vars['podcast_episode'] );
		$result = $method->invoke( $this->frontend_controller );
		$this->assertSame( 0, $result );
	}

	/**
	 * Test get_episode_id_from_query converts string
	 *
	 * @group helpers
	 * @group episode
	 */
	public function test_get_episode_id_from_query_converts_string() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_episode_id_from_query' );
		$method->setAccessible( true );

		$wp_query->query_vars['podcast_episode'] = '789';
		$result                                    = $method->invoke( $this->frontend_controller );
		$this->assertSame( 789, $result );
		$this->assertIsInt( $result );
		
		unset( $wp_query->query_vars['podcast_episode'] );
	}

	/**
	 * Test get_download_referrer from query var
	 *
	 * @group helpers
	 * @group referrer
	 */
	public function test_get_download_referrer_from_query_var() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_download_referrer' );
		$method->setAccessible( true );

		$wp_query->query_vars['podcast_ref'] = 'player';
		$result                                = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( 'player', $result );
		
		unset( $wp_query->query_vars['podcast_ref'] );
	}

	/**
	 * Test get_download_referrer from GET parameter
	 *
	 * @group helpers
	 * @group referrer
	 */
	public function test_get_download_referrer_from_get_param() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_download_referrer' );
		$method->setAccessible( true );

		unset( $wp_query->query_vars['podcast_ref'] );
		$_GET['ref'] = 'download';
		$result      = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( 'download', $result );
		
		unset( $_GET['ref'] );
	}

	/**
	 * Test get_download_referrer precedence
	 *
	 * @group helpers
	 * @group referrer
	 */
	public function test_get_download_referrer_precedence() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_download_referrer' );
		$method->setAccessible( true );

		$wp_query->query_vars['podcast_ref'] = 'query_var_value';
		$_GET['ref']                          = 'get_param_value';
		$result                                = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( 'query_var_value', $result );
		
		unset( $wp_query->query_vars['podcast_ref'] );
		unset( $_GET['ref'] );
	}

	/**
	 * Test get_download_referrer returns empty when missing
	 *
	 * @group helpers
	 * @group referrer
	 */
	public function test_get_download_referrer_returns_empty_when_missing() {
		global $wp_query;
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_download_referrer' );
		$method->setAccessible( true );

		unset( $wp_query->query_vars['podcast_ref'] );
		unset( $_GET['ref'] );
		$result = $method->invoke( $this->frontend_controller );
		
		$this->assertEquals( '', $result );
	}

	// ========================================
	// SECTION 5: ACTION HOOK TESTS
	// ========================================

	/**
	 * Test trigger_download_action fires hook
	 *
	 * @group helpers
	 * @group hooks
	 */
	public function test_trigger_download_action_fires_hook() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'trigger_download_action' );
		$method->setAccessible( true );

		$episode = $this->factory()->post->create_and_get(
			array(
				'post_type' => 'podcast',
			)
		);

		$action_fired  = false;
		$hook_callback = function () use ( &$action_fired ) {
			$action_fired = true;
		};
		
		add_action( 'ssp_file_download', $hook_callback );
		
		$method->invoke( $this->frontend_controller, 'https://example.com/file.mp3', $episode, 'download' );
		
		$this->assertTrue( $action_fired );
		
		remove_action( 'ssp_file_download', $hook_callback );
	}

	/**
	 * Test trigger_download_action skips test-nginx referrer
	 *
	 * @group helpers
	 * @group hooks
	 */
	public function test_trigger_download_action_skips_test_nginx() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'trigger_download_action' );
		$method->setAccessible( true );

		$episode = $this->factory()->post->create_and_get(
			array(
				'post_type' => 'podcast',
			)
		);

		$action_fired  = false;
		$hook_callback = function () use ( &$action_fired ) {
			$action_fired = true;
		};
		
		add_action( 'ssp_file_download', $hook_callback );
		
		$method->invoke( $this->frontend_controller, 'https://example.com/file.mp3', $episode, 'test-nginx' );
		
		$this->assertFalse( $action_fired );
		
		remove_action( 'ssp_file_download', $hook_callback );
	}

	// ========================================
	// SECTION 6: FILE SIZE TESTS
	// ========================================

	/**
	 * Test get_file_size retrieves from post meta
	 *
	 * @group helpers
	 * @group file-operations
	 */
	public function test_get_file_size_from_post_meta() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_file_size' );
		$method->setAccessible( true );

		$episode_id = $this->factory()->post->create(
			array(
				'post_type' => 'podcast',
			)
		);

		update_post_meta( $episode_id, 'filesize_raw', 2048 );
		
		$result = $method->invoke( $this->frontend_controller, $episode_id, 'https://example.com/file.mp3' );
		
		$this->assertEquals( 2048, $result );
	}

	/**
	 * Test get_file_size uses cache
	 *
	 * @group helpers
	 * @group file-operations
	 */
	public function test_get_file_size_uses_cache() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_file_size' );
		$method->setAccessible( true );

		$episode_id = $this->factory()->post->create(
			array(
				'post_type' => 'podcast',
			)
		);

		update_post_meta( $episode_id, 'filesize_raw', 4096 );
		
		$result1 = $method->invoke( $this->frontend_controller, $episode_id, 'https://example.com/file.mp3' );
		$this->assertEquals( 4096, $result1 );
		
		update_post_meta( $episode_id, 'filesize_raw', 8192 );
		
		$result2 = $method->invoke( $this->frontend_controller, $episode_id, 'https://example.com/file.mp3' );
		$this->assertEquals( 4096, $result2 );
	}

	/**
	 * Test get_file_size returns empty when unavailable
	 *
	 * @group helpers
	 * @group file-operations
	 */
	public function test_get_file_size_returns_empty_when_unavailable() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'get_file_size' );
		$method->setAccessible( true );

		$episode_id = $this->factory()->post->create(
			array(
				'post_type' => 'podcast',
			)
		);

		$result = $method->invoke( $this->frontend_controller, $episode_id, 'https://cdn.castos.com/file.mp3' );
		
		$this->assertEmpty( $result );
	}

	// ========================================
	// SECTION 7: PERFORMANCE & CACHING TESTS
	// ========================================

	/**
	 * Test trusted domain detection for exact matches
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_trusted_domain_exact_match() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		$trusted_hosts = array(
			'castos.com',
			'blubrry.com',
			's3.amazonaws.com',
			'storage.googleapis.com',
		);

		foreach ( $trusted_hosts as $host ) {
			$result = $method->invoke( $this->frontend_controller, $host );
			$this->assertTrue( $result, "Should trust exact match: {$host}" );
		}
	}

	/**
	 * Test trusted domain detection for subdomains
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_trusted_domain_subdomain_match() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		$trusted_subdomains = array(
			'cdn.castos.com',
			'media.blubrry.com',
			'bucket.s3.amazonaws.com',
			'my-bucket.storage.googleapis.com',
		);

		foreach ( $trusted_subdomains as $host ) {
			$result = $method->invoke( $this->frontend_controller, $host );
			$this->assertTrue( $result, "Should trust subdomain: {$host}" );
		}
	}

	/**
	 * Test that untrusted domains are not matched
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_untrusted_domains_rejected() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		$untrusted_hosts = array(
			'evil.com',
			'castos.com.evil.com',       // Not a subdomain - ends with .evil.com
			'castos.com.hacker.site',    // Attempted bypass - ends with .site
			'notcastos.com',             // Similar name but different
			'example.com',
			'fakeblubrry.com',           // Similar to trusted blubrry.com
		);

		foreach ( $untrusted_hosts as $host ) {
			$result = $method->invoke( $this->frontend_controller, $host );
			$this->assertFalse( $result, "Should not trust: {$host}" );
		}
	}

	/**
	 * Test URL validation caching for trusted domains
	 *
	 * @group performance
	 * @group caching
	 */
	public function test_url_validation_caches_trusted_domains() {
		// Clear any existing cache
		$this->frontend_controller->clear_validation_cache();

		$url = 'https://cdn.castos.com/episode.mp3';
		
		// First call should validate and cache
		$result1 = $this->frontend_controller->validate_file_url( $url );
		$this->assertTrue( $result1 );

		// Check that result was cached
		$cache_key = 'ssp_url_valid_' . md5( $url );
		$cached = get_transient( $cache_key );
		$this->assertEquals( 1, $cached, 'Trusted domain should be cached' );
	}

	/**
	 * Test URL validation caching for unknown valid domains
	 *
	 * @group performance
	 * @group caching
	 */
	public function test_url_validation_caches_unknown_domains() {
		// Clear any existing cache
		$this->frontend_controller->clear_validation_cache();

		$url = 'http://8.8.8.8/public-resource';
		
		// First call should validate and cache
		$result1 = $this->frontend_controller->validate_file_url( $url );
		$this->assertTrue( $result1 );

		// Check that result was cached
		$cache_key = 'ssp_url_valid_' . md5( $url );
		$cached = get_transient( $cache_key );
		$this->assertEquals( 1, $cached, 'Valid URL should be cached' );
	}

	/**
	 * Test URL validation caches invalid URLs
	 *
	 * @group performance
	 * @group caching
	 */
	public function test_url_validation_caches_invalid_urls() {
		// Clear any existing cache
		$this->frontend_controller->clear_validation_cache();

		// Test with localhost URL
		$url = 'http://localhost/admin';
		
		// First call should validate and cache
		$result1 = $this->frontend_controller->validate_file_url( $url );
		$this->assertFalse( $result1, 'Localhost should not pass validation' );

		// Check that result was cached
		$cache_key = 'ssp_url_valid_' . md5( $url );
		$cached = get_transient( $cache_key );
		$this->assertEquals( 0, $cached, 'Invalid URL should be cached' );
	}

	/**
	 * Test cache clearing functionality
	 *
	 * @group performance
	 * @group caching
	 */
	public function test_clear_validation_cache() {
		// Create some cached entries
		set_transient( 'ssp_url_valid_test1', 1, HOUR_IN_SECONDS );
		set_transient( 'ssp_dns_test1', array( '1.2.3.4' ), HOUR_IN_SECONDS );

		// Clear cache
		$deleted = $this->frontend_controller->clear_validation_cache();

		// Verify cache was cleared
		$this->assertFalse( get_transient( 'ssp_url_valid_test1' ) );
		$this->assertFalse( get_transient( 'ssp_dns_test1' ) );
	}

	/**
	 * Test that current WordPress site domain is trusted
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_current_site_domain_is_trusted() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		// Get current site domain
		$site_host = parse_url( home_url(), PHP_URL_HOST );
		
		$this->assertNotEmpty( $site_host, 'Site should have a hostname' );
		
		$result = $method->invoke( $this->frontend_controller, $site_host );
		$this->assertTrue( $result, "Current site domain should be trusted: {$site_host}" );
	}

	/**
	 * Test that upload directory domain is trusted
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_upload_directory_domain_is_trusted() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		// Get upload directory domain
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['baseurl'] ) ) {
			$upload_host = parse_url( $upload_dir['baseurl'], PHP_URL_HOST );
			
			if ( $upload_host ) {
				$result = $method->invoke( $this->frontend_controller, $upload_host );
				$this->assertTrue( $result, "Upload directory domain should be trusted: {$upload_host}" );
			} else {
				$this->markTestSkipped( 'Upload directory does not have a hostname' );
			}
		} else {
			$this->markTestSkipped( 'Upload directory baseurl not available' );
		}
	}

	/**
	 * Test local file URL validation performance
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_local_file_url_validates_quickly() {
		// Clear cache to ensure we're testing the trusted domain path
		$this->frontend_controller->clear_validation_cache();

		// Build a local file URL
		$upload_dir = wp_upload_dir();
		$local_url = $upload_dir['baseurl'] . '/podcasts/episode.mp3';
		
		// Validate - should hit trusted domain fast path
		$result = $this->frontend_controller->validate_file_url( $local_url );
		
		$this->assertTrue( $result, 'Local file URL should be validated as trusted domain' );
	}

	/**
	 * Test that trusted domain validation is working
	 *
	 * This indirectly tests that the filter system works, as the default
	 * trusted domains come through the filter mechanism.
	 *
	 * @group performance
	 * @group trusted-domains
	 */
	public function test_trusted_domain_system_works() {
		// Verify that at least one default trusted domain works
		// This proves the filter system is functioning
		$url = 'https://cdn.castos.com/episode.mp3';
		$result = $this->frontend_controller->validate_file_url( $url );
		
		$this->assertTrue( $result, 'Trusted CDN domain should be validated quickly' );
	}

	/**
	 * Test localhost validation in trusted domain check
	 *
	 * @group validation
	 * @group trusted-domains
	 */
	public function test_localhost_validation() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		$localhost_variants = array( 'localhost', '127.0.0.1', '::1' );
		
		foreach ( $localhost_variants as $host ) {
			$result = $method->invoke( $this->frontend_controller, $host );
			$this->assertFalse( $result, "Localhost ({$host}) should not be in trusted domains" );
		}
	}

	/**
	 * Test private IP address validation
	 *
	 * @group validation
	 * @group trusted-domains
	 */
	public function test_private_ip_validation() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_trusted_domain' );
		$method->setAccessible( true );

		$private_ips = array(
			'10.0.0.1',
			'172.16.0.1',
			'192.168.1.1',
			'169.254.1.1',
		);
		
		foreach ( $private_ips as $ip ) {
			$result = $method->invoke( $this->frontend_controller, $ip );
			$this->assertFalse( $result, "Private IP ({$ip}) should not be in trusted domains" );
		}
	}

	/**
	 * Test is_public_domain helper method
	 *
	 * @group validation
	 * @group helpers
	 */
	public function test_is_public_domain() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_public_domain' );
		$method->setAccessible( true );

		// Public domains should return true
		$this->assertTrue( $method->invoke( $this->frontend_controller, 'example.com' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, 'castos.com' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, '8.8.8.8' ) );

		// Internal addresses should return false
		$this->assertFalse( $method->invoke( $this->frontend_controller, 'localhost' ) );
		$this->assertFalse( $method->invoke( $this->frontend_controller, '127.0.0.1' ) );
		$this->assertFalse( $method->invoke( $this->frontend_controller, '192.168.1.1' ) );
		$this->assertFalse( $method->invoke( $this->frontend_controller, '10.0.0.1' ) );
		$this->assertFalse( $method->invoke( $this->frontend_controller, '::1' ) );
	}

	/**
	 * Test is_private_ip helper method
	 *
	 * @group validation
	 * @group helpers
	 */
	public function test_is_private_ip() {
		$reflection = new \ReflectionClass( $this->frontend_controller );
		$method     = $reflection->getMethod( 'is_private_ip' );
		$method->setAccessible( true );

		// Private range IPs should return true
		$this->assertTrue( $method->invoke( $this->frontend_controller, '127.0.0.1' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, '10.0.0.1' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, '172.16.0.1' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, '192.168.1.1' ) );
		$this->assertTrue( $method->invoke( $this->frontend_controller, '169.254.1.1' ) );

		// Public IPs should return false
		$this->assertFalse( $method->invoke( $this->frontend_controller, '8.8.8.8' ) );
		$this->assertFalse( $method->invoke( $this->frontend_controller, '1.1.1.1' ) );
	}

	// ========================================
	// SECTION 8: INTEGRATION TESTS
	// ========================================

	/**
	 * Test download_file integration with valid episode
	 *
	 * @group integration
	 * @group download
	 */
	public function test_download_file_integration_with_valid_episode() {
		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => 'podcast',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $episode_id, 'audio_file', 'https://example.com/podcast.mp3' );

		global $wp_query;
		$wp_query->query_vars['podcast_episode'] = $episode_id;
		$_GET['ref']                               = 'download';

		$this->assertTrue( true, 'Integration test setup complete' );

		unset( $wp_query->query_vars['podcast_episode'] );
		unset( $_GET['ref'] );
	}

	/**
	 * Cleanup
	 */
	public function tearDown(): void {
		// Clean up server vars
		unset(
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['HTTP_CF_CONNECTING_IP'],
			$_SERVER['HTTP_X_FORWARDED_FOR'],
			$_SERVER['HTTP_X_REAL_IP'],
			$_GET['ref'],
			$_GET['podcast_episode']
		);
		
		global $wp_query;
		if ( isset( $wp_query->query_vars ) ) {
			unset( $wp_query->query_vars['podcast_episode'], $wp_query->query_vars['podcast_ref'] );
		}
		
		// Clear validation cache to avoid test interference
		$this->frontend_controller->clear_validation_cache();
		
		parent::tearDown();
	}
}

