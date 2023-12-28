<?php

/**
 * Abstract Entity.
 *
 * @package SeriouslySimplePodcasting
 * @since 2.23.0
 * */

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract entity class.
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
    public function __construct( $properties ) {
        foreach ( get_object_vars( $this ) as $k => $v ) {
            if ( is_array( $properties ) ) {
                $this->fill_with_array( $properties, $k );
            } elseif ( is_object( $properties ) ) {
                $this->fill_with_object( $properties, $k );
            }
        }
    }

    protected function fill_with_object( $properties, $k ) {
        if ( isset( $properties->{$k} ) ) {
            $val = $properties->{$k};

            $this->{$k} = $this->guess_property_type( $val );
        }
    }

    protected function fill_with_array( $properties, $k ) {
        if ( isset( $properties[ $k ] ) ) {
            $val = $properties[ $k ];

            $this->{$k} = $this->guess_property_type( $val );
        }
    }

    protected function guess_property_type( $val ) {
        if ( is_numeric( $val ) ) {
			$val = strval( $val );
            $val = ( false === strpos( '.', $val ) || false === strpos( ',', $val ) ) ?
                intval( $val ) :
                floatval( $val );
        }

        return $val;
    }
}
