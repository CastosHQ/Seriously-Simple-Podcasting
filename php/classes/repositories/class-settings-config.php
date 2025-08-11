<?php

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
	 * @var callable $config_callback
	 * */
	private $config_callback;

	/**
	 * @var array $config
	 */
	private $config;

	/**
	 * @param callable $config_callback
	 */
	public function __construct( $config_callback ) {
		$this->config_callback = $config_callback;
	}

	/**
	 * @return array|mixed
	 */
	public function get_config() {
		if ( ! $this->config ) {
			$this->config = call_user_func( $this->config_callback );
		}

		return $this->config;
	}

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
	 * #[\ReturnTypeWillChange]
	 *
	 * @param $offset
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$config = $this->get_config();
		return isset( $config[ $offset ] );
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->config[ $offset ] );
	}

	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$config = $this->get_config();

		return array_key_exists( $offset, $config ) ? $config[ $offset ] : null;
	}

	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new \ArrayIterator( $this->get_config() );
	}

	#[\ReturnTypeWillChange]
	public function count() {
		return count( $this->get_config() );
	}
}
