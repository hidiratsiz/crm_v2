<?php

namespace App\Core;

/**
 * Minimal .env loader — no Composer package needed (no vlucas/phpdotenv).
 * Reads KEY=VALUE lines from a .env file into getenv()/$_ENV, so config.php
 * can read secrets from there instead of holding them directly in code.
 *
 * Supports:
 *   KEY=value
 *   KEY="value with spaces"
 *   KEY='value with spaces'
 *   # comments and blank lines (ignored)
 *
 * Does NOT overwrite a variable that's already set in the real server
 * environment (e.g. via Apache SetEnv or a hosting panel's "environment
 * variables" feature) — that always takes priority over .env.
 */
class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || substr($line, 0, 1) === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip one layer of matching surrounding quotes, if present
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key !== '' && getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        return ($value === false || $value === null || $value === '') ? $default : $value;
    }
}
