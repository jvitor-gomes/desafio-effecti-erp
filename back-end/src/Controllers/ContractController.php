<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\ContractService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContractController
{
    private ContractService $service;

    public function __construct(?ContractService $service = null)
    {
        $this->service = $service ?? new ContractService();
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

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::success($response, $this->service->findById((int) $args['id']));
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->create($data), 'Contrato criado com sucesso.', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->update((int) $args['id'], $data), 'Contrato atualizado com sucesso.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->service->delete((int) $args['id']);

        return JsonResponse::success($response, null, 'Contrato removido com sucesso.');
    }

    // PATCH dedicado: altera status para cancelado (regras de negócio no serviço)
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::success($response, $this->service->cancel((int) $args['id']), 'Contrato cancelado com sucesso.');
    }

    // Sub-recurso /items: corpo validado no ContractService + ContractItemValidator
    public function addItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->addItem((int) $args['id'], $data), 'Item adicionado com sucesso.', 201);
    }

    public function updateItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success(
            $response,
            $this->service->updateItem((int) $args['id'], (int) $args['itemId'], $data),
            'Item atualizado com sucesso.'
        );
    }

    public function removeItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::success(
            $response,
            $this->service->removeItem((int) $args['id'], (int) $args['itemId']),
            'Item removido com sucesso.'
        );
    }
}
