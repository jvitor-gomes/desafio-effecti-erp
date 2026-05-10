<?php

declare(strict_types=1);

namespace App\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;

class HttpExceptionHandler extends ErrorHandler
{
    // Resposta JSON única para validação, negócio, 404 Slim e erro genérico 500
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;
        $status = 500;
        $payload = ['success' => false, 'message' => 'Erro interno do servidor.'];

        if ($exception instanceof ValidationException) {
            $status = 422;
            $payload = ['success' => false, 'message' => $exception->getMessage(), 'errors' => $exception->getErrors()];
        } elseif ($exception instanceof BusinessException) {
            $status = 400;
            $payload = ['success' => false, 'message' => $exception->getMessage()];
        } elseif ($exception instanceof NotFoundException || $exception instanceof HttpNotFoundException) {
            $status = 404;
            $payload = ['success' => false, 'message' => $exception->getMessage() ?: 'Recurso nao encontrado.'];
        }

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
