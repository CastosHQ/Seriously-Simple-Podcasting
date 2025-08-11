<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * Class UUID_Handler
 *
 * Generates RFC 4122 compliant Universally Unique Identifiers (UUID) versions 3, 4, and 5.
 * This is a pure PHP implementation that validates against the OSSP UUID Tool.
 * For named-based UUIDs (v3 and v5), output matches OSSP exactly.
 *
 * @package Seriously Simple Podcasting
 * @since 2.9.0
 * @author Andrew Moore, Serhiy Zakharchenko
 * @link http://www.php.net/manual/en/function.uniqid.php#94959
 */
class UUID_Handler implements Service {
	/**
	 * Generate version 3 UUID.
	 *
	 * Version 3 UUIDs are name-based using MD5 hashing. They require a namespace
	 * (another valid UUID) and a name. Given the same namespace and name,
	 * the output is always the same.
	 *
	 * @param string $namespace The namespace UUID.
	 * @param string $name     The name to generate the UUID for.
	 *
	 * @return false|string UUID string or false on failure.
	 */
	public function v3( $namespace, $name ) {
		if ( ! $this->is_valid( $namespace ) ) {
			return false;
		}

		// Get hexadecimal components of namespace
		$nhex = str_replace( array( '-', '{', '}' ), '', $namespace );

		// Binary Value
		$nstr = '';

		// Convert Namespace UUID to bits
		for ( $i = 0; $i < strlen( $nhex ); $i += 2 ) {
			$nstr .= chr( hexdec( $nhex[ $i ] . $nhex[ $i + 1 ] ) );
		}

		// Calculate hash value
		$hash = md5( $nstr . $name );

		return sprintf(
			'%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr( $hash, 0, 8 ),
			// 16 bits for "time_mid"
			substr( $hash, 8, 4 ),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 3
			( hexdec( substr( $hash, 12, 4 ) ) & 0x0fff ) | 0x3000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			( hexdec( substr( $hash, 16, 4 ) ) & 0x3fff ) | 0x8000,
			// 48 bits for "node"
			substr( $hash, 20, 12 )
		);
	}

	/**
	 * Generate version 4 UUID.
	 *
	 * Version 4 UUIDs are pseudo-random using mt_rand().
	 * Each UUID is unique with very high probability.
	 *
	 * @return string Generated UUID string.
	 */
	public static function v4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,
			// 48 bits for "node"
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}

	/**
	 * Generate version 5 UUID.
	 *
	 * Version 5 UUIDs are name-based using SHA-1 hashing. They require a namespace
	 * (another valid UUID) and a name. Given the same namespace and name,
	 * the output is always the same.
	 *
	 * @param string $namespace The namespace UUID.
	 * @param string $name     The name to generate the UUID for.
	 *
	 * @return false|string UUID string or false on failure.
	 */
	public static function v5( $namespace, $name ) {
		if ( ! self::is_valid( $namespace ) ) {
			return false;
		}

		// Get hexadecimal components of namespace
		$nhex = str_replace( array( '-', '{', '}' ), '', $namespace );

		// Binary Value
		$nstr = '';

		// Convert Namespace UUID to bits
		for ( $i = 0; $i < strlen( $nhex ); $i += 2 ) {
			$nstr .= chr( hexdec( $nhex[ $i ] . $nhex[ $i + 1 ] ) );
		}

		// Calculate hash value
		$hash = sha1( $nstr . $name );

		return sprintf(
			'%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr( $hash, 0, 8 ),
			// 16 bits for "time_mid"
			substr( $hash, 8, 4 ),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
			( hexdec( substr( $hash, 12, 4 ) ) & 0x0fff ) | 0x5000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			( hexdec( substr( $hash, 16, 4 ) ) & 0x3fff ) | 0x8000,
			// 48 bits for "node"
			substr( $hash, 20, 12 )
		);
	}

	/**
	 * Validate a UUID string.
	 *
	 * @param string $uuid UUID string to validate.
	 *
	 * @return bool True if valid UUID, false otherwise.
	 */
	public static function is_valid( $uuid ) {
		return preg_match(
			'/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
							'[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
			$uuid
		) === 1;
	}
}
