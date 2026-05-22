<?php

declare(strict_types=1);

/**
 * Load environment variables from .env into $_ENV and getenv().
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return (string) $value;
}

$root = dirname(__DIR__);
loadEnv($root . DIRECTORY_SEPARATOR . '.env');
