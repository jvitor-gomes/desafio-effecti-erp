<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $host = $_ENV['DB_HOST'] ?? 'postgres';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $dbname = $_ENV['DB_DATABASE'] ?? 'erp_contracts';
        $user = $_ENV['DB_USERNAME'] ?? 'erp_user';
        $pass = $_ENV['DB_PASSWORD'] ?? 'erp_secret';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname);

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::boot();
        }

        return self::$pdo;
    }
}
