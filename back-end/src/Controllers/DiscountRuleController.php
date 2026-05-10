<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Services\DiscountRuleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DiscountRuleController
{
    private DiscountRuleService $service;

    public function __construct(?DiscountRuleService $service = null)
    {
        $this->service = $service ?? new DiscountRuleService();
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

        return JsonResponse::success($response, $this->service->create($data), 'Regra criada com sucesso.', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        return JsonResponse::success($response, $this->service->update((int) $args['id'], $data), 'Regra atualizada com sucesso.');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->service->delete((int) $args['id']);

        return JsonResponse::success($response, null, 'Regra removida com sucesso.');
    }
}
