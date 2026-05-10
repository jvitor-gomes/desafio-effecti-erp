<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\ClientController;
use App\Controllers\ContractController;
use App\Controllers\DiscountRuleController;
use App\Controllers\ServiceController;
use App\Helpers\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class ApiRoutes
{
    public static function register(App $app): void
    {
        $app->get('/health', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return JsonResponse::success($response, ['status' => 'ok']);
        });

        // Recursos REST agrupados sob /api
        $app->group('/api', function (RouteCollectorProxy $group): void {
            $group->get('/clients', [ClientController::class, 'index']);
            $group->post('/clients', [ClientController::class, 'store']);
            $group->get('/clients/{id}', [ClientController::class, 'show']);
            $group->put('/clients/{id}', [ClientController::class, 'update']);
            $group->delete('/clients/{id}', [ClientController::class, 'destroy']);

            $group->get('/services', [ServiceController::class, 'index']);
            $group->post('/services', [ServiceController::class, 'store']);
            $group->get('/services/{id}', [ServiceController::class, 'show']);
            $group->put('/services/{id}', [ServiceController::class, 'update']);
            $group->delete('/services/{id}', [ServiceController::class, 'destroy']);

            $group->get('/contracts', [ContractController::class, 'index']);
            $group->get('/contracts/{id}', [ContractController::class, 'show']);
            $group->post('/contracts', [ContractController::class, 'store']);
            $group->put('/contracts/{id}', [ContractController::class, 'update']);
            $group->delete('/contracts/{id}', [ContractController::class, 'destroy']);
            $group->patch('/contracts/{id}/cancel', [ContractController::class, 'cancel']);
            $group->post('/contracts/{id}/items', [ContractController::class, 'addItem']);
            $group->put('/contracts/{id}/items/{itemId}', [ContractController::class, 'updateItem']);
            $group->delete('/contracts/{id}/items/{itemId}', [ContractController::class, 'removeItem']);

            $group->get('/discount-rules', [DiscountRuleController::class, 'index']);
            $group->post('/discount-rules', [DiscountRuleController::class, 'store']);
            $group->put('/discount-rules/{id}', [DiscountRuleController::class, 'update']);
            $group->delete('/discount-rules/{id}', [DiscountRuleController::class, 'destroy']);
        });
    }
}
