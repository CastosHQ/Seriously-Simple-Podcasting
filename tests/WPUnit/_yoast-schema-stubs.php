<?php
/**
 * Stub for the Yoast schema base class, which the WPUnit suite never loads.
 *
 * The suite activates Seriously Simple Podcasting only, so the real
 * Abstract_Schema_Piece is absent and the autoloader cannot even include the
 * classes that extend it. Guarded by class_exists() so the file is a no-op
 * when Yoast SEO is active.
 */

namespace Yoast\WP\SEO\Generators\Schema;

if ( ! class_exists( __NAMESPACE__ . '\Abstract_Schema_Piece' ) ) {

	/**
	 * Mirrors the public surface of Yoast's schema piece base class.
	 */
	abstract class Abstract_Schema_Piece {

		/**
		 * The meta tags context. Untyped in Yoast, so tests may assign any object
		 * exposing the properties the piece reads.
		 *
		 * @var object
		 */
		public $context;

		/**
		 * The helpers surface.
		 *
		 * @var object
		 */
		public $helpers;

		/**
		 * Optional identifier for this schema piece.
		 *
		 * @var string
		 */
		public $identifier;

		/**
		 * Generates the schema piece.
		 *
		 * @return mixed
		 */
		abstract public function generate();

		/**
		 * Determines whether the schema piece is needed.
		 *
		 * @return bool
		 */
		abstract public function is_needed();
	}
}
