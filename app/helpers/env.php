<?php
/**
 * Tiny .env loader (no external dependencies).
 *
 * Reads KEY=VALUE pairs from a .env file at the project root and pushes them
 * into the environment so the existing getenv()-based config keeps working
 * unchanged. This makes switching between development and production on
 * localhost as easy as editing one file — no need to set real OS env vars.
 *
 * Rules:
 *  - Lines starting with # are comments and are ignored.
 *  - Blank lines are ignored.
 *  - Surrounding single or double quotes around a value are stripped.
 *  - Existing real environment variables ALWAYS win (so server-level config
 *    in production is never overridden by a stray .env file).
 */

if (!function_exists('loadEnv')) {
    /**
     * Load a .env file into the environment.
     *
     * @param string $path Absolute path to the .env file.
     */
    function loadEnv(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return; // No .env file — silently fall back to getenv() defaults.
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and any line without a key=value structure.
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            // Strip a single pair of surrounding quotes, if present.
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Real environment variables take precedence — never override them.
            if (getenv($key) !== false) {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('filterEnvBool')) {
    /**
     * Interpret an environment value as a boolean feature flag.
     *
     * Accepts: true/false, 1/0, yes/no, on/off (case-insensitive).
     * Returns $default when the value is unset (getenv() returned false) or
     * is an empty/unrecognised string, so flags have safe fallbacks.
     *
     * @param string|false $value   Raw value from getenv().
     * @param bool         $default Value to use when unset/unrecognised.
     */
    function filterEnvBool($value, bool $default): bool
    {
        if ($value === false || $value === null) {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return $default;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }
}
