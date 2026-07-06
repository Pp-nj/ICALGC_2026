<?php

namespace App\Core;

/**
 * Minimal .env loader — no external dependency.
 * Reads KEY=VALUE lines from a .env file and injects them via putenv()/$_ENV
 * without overriding variables already set in the real environment.
 */
class Env
{
    public static function load(string $path): void
    {
        if (!is_readable($path)) return;

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name  = trim($name);
            $value = trim($value);

            if ($name === '') continue;
            if (strlen($value) >= 2 && (
                ($value[0] === '"' && $value[-1] === '"') ||
                ($value[0] === "'" && $value[-1] === "'")
            )) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}
