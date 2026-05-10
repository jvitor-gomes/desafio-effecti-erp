<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\ContractItemRepository;
use App\Repositories\ServiceRepository;
use App\Validators\ServiceValidator;

class ServiceCatalogService
{
    private ServiceRepository $services;
    private ContractItemRepository $contractItems;

    public function __construct(
        ?ServiceRepository $services = null,
        ?ContractItemRepository $contractItems = null,
    ) {
        $this->services = $services ?? new ServiceRepository();
        $this->contractItems = $contractItems ?? new ContractItemRepository();
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->services->paginate($page, $perPage);
    }

    public function create(array $data): array
    {
        $attributes = ServiceValidator::validatedCreateAttributes($data);
        $service = $this->services->create($attributes);

        return $service->toArray();
    }

    public function findById(int $id): array
    {
        $service = $this->services->find($id);
        if (!$service) {
            throw new NotFoundException('Servico nao encontrado.');
        }

        return $service->toArray();
    }

    public function update(int $id, array $data): array
    {
        $service = $this->services->find($id);
        if (!$service) {
            throw new NotFoundException('Servico nao encontrado.');
        }

        $payload = ServiceValidator::validatedUpdatePatch($data);

        if (!empty($payload)) {
            $this->services->update($service, $payload);
        }

        return $this->services->find($id)?->toArray() ?? $service->toArray();
    }

    public function delete(int $id): void
    {
        $service = $this->services->find($id);
        if (!$service) {
            throw new NotFoundException('Servico nao encontrado.');
        }

        // Integridade: não remove serviço ainda referenciado em itens de contrato
        ServiceValidator::assertCanDelete($this->contractItems->existsForServiceId($id));

        $this->services->delete($service);
    }
}
