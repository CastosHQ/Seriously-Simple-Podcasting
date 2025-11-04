<?php
/**
 * Abstract Entity.
 *
 * @package SeriouslySimplePodcasting
 * @since 2.23.0
 */

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract entity class.
 *
 * @since 2.23.0
 * @author Serhiy Zakharchenko
 */
abstract class Abstract_Entity {
	/**
	 * Constructor
	 * Propagates all the properties from the given array
	 *
	 * @param array $properties Array of message properties.
	 */
	public function __construct( $properties = array() ) {
		foreach ( get_object_vars( $this ) as $k => $v ) {
			if ( is_array( $properties ) ) {
				$this->fill_with_array( $properties, $k );
			} elseif ( is_object( $properties ) ) {
				$this->fill_with_object( $properties, $k );
			}
		}
	}

	/**
	 * Fills entity properties from an object.
	 *
	 * @param object $properties Object containing properties.
	 * @param string $k          Property key to fill.
	 * @return void
	 */
	protected function fill_with_object( $properties, $k ) {
		if ( isset( $properties->{$k} ) ) {
			$val = $properties->{$k};

			$this->{$k} = $this->guess_property_type( $val );
		}
	}

	/**
	 * Fills entity properties from an array.
	 *
	 * @param array  $properties Array containing properties.
	 * @param string $k          Property key to fill.
	 * @return void
	 */
	protected function fill_with_array( $properties, $k ) {
		if ( isset( $properties[ $k ] ) ) {
			$val = $properties[ $k ];

			$this->{$k} = $this->guess_property_type( $val );
		}
	}

	/**
	 * Guesses and converts property type based on value.
	 *
	 * @param mixed $val Property value to convert.
	 * @return mixed Converted property value.
	 */
	protected function guess_property_type( $val ) {
		if ( is_numeric( $val ) ) {
			$val = (string) $val;
			// If there is neither '.' nor ',' we treat as int; otherwise as float.
			$val = ( false === strpos( $val, '.' ) && false === strpos( $val, ',' ) )
				? (int) $val
				: (float) $val;
		}
		return $val;
	}
}
