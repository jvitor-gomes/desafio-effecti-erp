<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\ClientService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClientController
{
    private ClientService $service;

    public function __construct(?ClientService $service = null)
    {
        $this->service = $service ?? new ClientService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        // page >= 1; per_page limitado para não sobrecarregar o banco
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($query['per_page'] ?? 10)));

        $result = $this->service->list($page, $perPage);

        return JsonResponse::paginated($response, $result['items'], $page, $perPage, $result['total']);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->create($data), 'Cliente criado com sucesso.', 201);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::success($response, $this->service->findById((int) $args['id']));
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->update((int) $args['id'], $data), 'Cliente atualizado com sucesso.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->service->delete((int) $args['id']);

        return JsonResponse::success($response, null, 'Cliente removido com sucesso.');
    }
}
