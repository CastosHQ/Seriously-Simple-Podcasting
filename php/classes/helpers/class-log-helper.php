<?php

namespace SeriouslySimplePodcasting\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Log_Helper {

	private $log_dir_path;
	private $log_dir_url;
	public $log_path;
	public $log_url;

	/**
	 * Log_Helper constructor.
	 */
	public function __construct() {
		$this->log_dir_path = SSP_PLUGIN_PATH . 'log' . DIRECTORY_SEPARATOR;
		$this->log_dir_url  = SSP_PLUGIN_URL . 'log' . DIRECTORY_SEPARATOR;
		$this->log_path     = $this->log_dir_path . 'ssp.log.' . date( 'd-m-y' ) . '.txt';
		$this->log_url      = $this->log_dir_url . 'ssp.log.' . date( 'd-m-y' ) . '.txt';
		$this->check_paths();
	}

	/**
	 * Checks if the logging paths exist, and attempts to create them
	 * Will only fire if SSP_DEBUG is set and true
	 *
	 * @return bool
	 */
	public function check_paths() {
		if ( ! defined( 'SSP_DEBUG' ) || ! SSP_DEBUG ) {
			return false;
		}
		if ( ! is_dir( $this->log_dir_path ) ) {
			mkdir( $this->log_dir_path );
		}
		if ( ! is_file( $this->log_path ) ) {
			file_put_contents( $this->log_path, '' ); //phpcs:ignore WordPress.WP
		}
	}

	/**
	 * Simple Logging function
	 *
	 * @param $message mixed debug message
	 * @param $data mixed debug data
	 *
	 * @return bool
	 */
	public function log( $message, $data = '' ) {
		if ( ! defined( 'SSP_DEBUG' ) || ! SSP_DEBUG ) {
			return false;
		}
		if ( ! empty( $data ) ) {
			$message = array( $message => $data );
		}
		$data_string = print_r( $message, true ) . PHP_EOL; //phpcs:ignore WordPress.PHP
		return file_put_contents( $this->log_path, $data_string, FILE_APPEND ); //phpcs:ignore WordPress.WP
	}
}
