<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module {

	public function waitForFile( $file = null ) {
		if ( ! ( getenv( 'CI' ) || getenv( 'CONTAINER' ) ) ) {
			codecept_debug( 'Not in CI/Container context: not waiting' );
			return;
		}

		$waitFor = $this->config['wait'] ?: 3;

		if ( $file === null ) {
			// Just wait some arbitrary time if we're in CI/Container context.
			codecept_debug( 'In CI/Container context: sleeping for ' . $waitFor );
			sleep( $waitFor );
			return;
		}

		codecept_debug( 'In CI/Container context: waiting for file ' . $file . ' for ' . $waitFor );

		$start = microtime( true );
		while ( ( microtime( true ) - $start ) < $waitFor ) {
			if ( file_exists( $file ) ) {
				return;
			}
			usleep( 200000 ); // 200ms backoff to avoid busy-waiting.
		}

		throw new \RuntimeException( 'Waited for ' . $waitFor . ' seconds but file still does not exist.' );
	}

	public function waitForElement() {
		sleep( 5 );
	}

	public function wait( $seconds ) {
		sleep( $seconds );
	}

	public function getConfig( $arg = null ) {
		$browser = $this->getModule( 'PhpBrowser' );
		return $browser->_getConfig( $arg );
	}

	public function getCurrentUrl() {
		$browser = $this->getModule( 'PhpBrowser' );

		return $browser->_getCurrentUri();
	}
}
