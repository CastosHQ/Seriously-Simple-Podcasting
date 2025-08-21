<?php
/**
 * Settings Config class.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.7.1
 */

namespace SeriouslySimplePodcasting\Repositories;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * Used to access settings on demand (lazy loading).
 *
 * @since 3.7.1
 * @package Seriously Simple Podcasting
 */
class Settings_Config implements Service, ArrayAccess, Countable, IteratorAggregate {
	/**
	 * Configuration callback function.
	 *
	 * @var callable
	 */
	private $config_callback;

	/**
	 * Configuration array.
	 *
	 * @var array|null
	 */
	private $config = null;

	/**
	 * Constructor.
	 *
	 * @param callable $config_callback Configuration callback function.
	 */
	public function __construct( $config_callback ) {
		$this->config_callback = $config_callback;
	}

	/**
	 * Get configuration array.
	 *
	 * @return array|mixed
	 */
	public function get_config() {
		if ( null === $this->config ) {
			$this->config = call_user_func( $this->config_callback );
		}

		return $this->config;
	}

	/**
	 * Set array offset.
	 *
	 * @param mixed $offset Array offset.
	 * @param mixed $value  Value to set.
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->config = $this->get_config();

		if ( is_null( $offset ) ) {
			$this->config[] = $value;
		} else {
			$this->config[ $offset ] = $value;
		}
	}

	/**
	 * Check if array offset exists.
	 *
	 * @param mixed $offset Array offset.
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$config = $this->get_config();
		return isset( $config[ $offset ] );
	}

	/**
	 * Unset array offset.
	 *
	 * @param mixed $offset Array offset.
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->config = $this->get_config();
		unset( $this->config[ $offset ] );
	}

	/**
	 * Get array offset value.
	 *
	 * @param mixed $offset Array offset.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$config = $this->get_config();

		return array_key_exists( $offset, $config ) ? $config[ $offset ] : null;
	}

	/**
	 * Get array iterator.
	 *
	 * @return \ArrayIterator
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new \ArrayIterator( $this->get_config() );
	}

	/**
	 * Get array count.
	 *
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count() {
		return count( $this->get_config() );
	}
}
