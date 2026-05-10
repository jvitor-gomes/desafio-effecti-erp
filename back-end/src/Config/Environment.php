<?php

declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

class Environment
{
    public static function load(): void
    {
        $rootPath = dirname(__DIR__, 2);
        if (file_exists($rootPath . '/.env')) {
            Dotenv::createImmutable($rootPath)->safeLoad();
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }

        $v = getenv($key);

        return $v !== false ? (string) $v : $default;
    }
}
