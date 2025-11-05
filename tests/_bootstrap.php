<?php

/**
 * Global Bootstrap File
 *
 * Loads environment variables from .env.testing into $_ENV and $_SERVER
 * so they can be accessed in test code.
 *
 * @since 3.14.3
 */

// Load .env.testing file if it exists
$envFile = __DIR__ . '/../.env.testing';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');
            if ($value === '') {
                continue;
            }

            // Set in both $_ENV and $_SERVER for compatibility
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

