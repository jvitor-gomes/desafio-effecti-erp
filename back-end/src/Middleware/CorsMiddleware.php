<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Environment;

class CorsMiddleware
{
    // OPTIONS é respondido em index.php; aqui só definem-se os cabeçalhos para o browser
    public static function handle(): void
    {
        $origin = Environment::get('CORS_ORIGIN', 'http://localhost:5173');

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
        header('Access-Control-Max-Age: 86400');
    }
}
