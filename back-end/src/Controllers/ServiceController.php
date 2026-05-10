<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\ServiceCatalogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServiceController
{
    private ServiceCatalogService $service;

    public function __construct(?ServiceCatalogService $service = null)
    {
        $this->service = $service ?? new ServiceCatalogService();
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

        return JsonResponse::success($response, $this->service->create($data), 'Servico criado com sucesso.', 201);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::success($response, $this->service->findById((int) $args['id']));
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->update((int) $args['id'], $data), 'Servico atualizado com sucesso.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->service->delete((int) $args['id']);

        return JsonResponse::success($response, null, 'Servico removido com sucesso.');
    }
}
