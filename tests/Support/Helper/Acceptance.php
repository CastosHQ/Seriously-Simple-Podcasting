<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

/**
 * Acceptance Helper
 *
 * Custom helper methods for acceptance tests.
 *
 * @since 3.14.3
 */
class Acceptance extends \Codeception\Module
{
    /**
     * Wait for file to exist (useful in CI/Container contexts)
     *
     * @since 3.14.3
     *
     * @param string|null $file File path to wait for, or null to just wait.
     * @return void
     * @throws \RuntimeException If file doesn't exist after waiting.
     */
    public function waitForFile($file = null): void
    {
        if (!(getenv('CI') || getenv('CONTAINER'))) {
            codecept_debug('Not in CI/Container context: not waiting');
            return;
        }

        $waitFor = $this->config['wait'] ?? 3;

        if ($file === null) {
            // Just wait some arbitrary time if we're in CI/Container context.
            codecept_debug('In CI/Container context: sleeping for ' . $waitFor);
            sleep($waitFor);
            return;
        }

        codecept_debug('In CI/Container context: waiting for file ' . $file . ' for ' . $waitFor);

        $start = microtime(true);
        while ((microtime(true) - $start) < $waitFor) {
            if (file_exists($file)) {
                return;
            }
            usleep(200000); // 200ms backoff to avoid busy-waiting.
        }

        throw new \RuntimeException('Waited for ' . $waitFor . ' seconds but file still does not exist.');
    }

    /**
     * Wait for element (sleep 5 seconds)
     *
     * @since 3.14.3
     *
     * @return void
     */
    public function waitForElement(): void
    {
        sleep(5);
    }

    /**
     * Wait for specified number of seconds
     *
     * @since 3.14.3
     *
     * @param int $seconds Number of seconds to wait.
     * @return void
     */
    public function wait(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * Get PhpBrowser configuration
     *
     * @since 3.14.3
     *
     * @param string|null $arg Configuration key to get, or null for all config.
     * @return mixed Configuration value or array.
     */
    public function getConfig($arg = null)
    {
        $browser = $this->getModule('PhpBrowser');
        return $browser->_getConfig($arg);
    }

    /**
     * Get current browser URL
     *
     * @since 3.14.3
     *
     * @return string Current URL.
     */
    public function getCurrentUrl(): string
    {
        $browser = $this->getModule('PhpBrowser');
        return $browser->_getCurrentUri();
    }
}

