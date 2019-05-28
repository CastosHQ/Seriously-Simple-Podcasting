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

	public function __construct() {
		$this->log_dir_path = SSP_PLUGIN_PATH . 'log' . DIRECTORY_SEPARATOR;
		$this->log_dir_url  = SSP_PLUGIN_URL . 'log' . DIRECTORY_SEPARATOR;
		$this->log_path     = $this->log_dir_path . 'ssp.log.' . date( 'd-m-y' ) . '.txt';
		$this->log_url      = $this->log_dir_url . 'ssp.log.' . date( 'd-m-y' ) . '.txt';
		$this->check_paths();
	}

	public function check_paths() {
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
	 * @param $message string debug message
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
		file_put_contents( $this->log_path, $data_string, FILE_APPEND ); //phpcs:ignore WordPress.WP
	}
}
