<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$rootPath = dirname(__DIR__);
if (file_exists($rootPath . '/.env')) {
    Dotenv\Dotenv::createImmutable($rootPath)->safeLoad();
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'postgres',
            'name' => $_ENV['DB_DATABASE'] ?? 'erp_contracts',
            'user' => $_ENV['DB_USERNAME'] ?? 'erp_user',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'erp_secret',
            'port' => $_ENV['DB_PORT'] ?? 5432,
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation',
];
