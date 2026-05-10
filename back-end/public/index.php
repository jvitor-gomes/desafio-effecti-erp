<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Environment;
use App\Exceptions\HttpExceptionHandler;
use App\Middleware\CorsMiddleware;
use App\Routes\ApiRoutes;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Environment::load();

CorsMiddleware::handle();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

Database::boot();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(
    filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    true,
    true
);
$errorMiddleware->setDefaultErrorHandler(new HttpExceptionHandler($app->getCallableResolver(), $app->getResponseFactory()));

ApiRoutes::register($app);

$app->run();
