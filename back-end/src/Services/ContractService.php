<?php

declare(strict_types=1);

namespace App\Services;

use App\Rules\DiscountCalculator;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Models\Contract;
use App\Repositories\ClientRepository;
use App\Repositories\ContractItemRepository;
use App\Repositories\ContractRepository;
use App\Repositories\ServiceRepository;
use App\Validators\ContractItemValidator;
use App\Validators\ContractValidator;

class ContractService
{
    private DiscountCalculator $discountCalculator;
    private ContractRepository $contracts;
    private ContractItemRepository $contractItems;
    private ClientRepository $clients;
    private ServiceRepository $services;

    public function __construct(?DiscountCalculator $discountCalculator = null)
    {
        $this->discountCalculator = $discountCalculator ?? new DiscountCalculator();
        $this->contracts = new ContractRepository();
        $this->contractItems = new ContractItemRepository();
        $this->clients = new ClientRepository();
        $this->services = new ServiceRepository();
    }

    // Mapeia saída do motor (discount_value) para o payload da API (discount)
    public function calculationForContract(array $items, array $contract = []): array
    {
        $full = $this->discountCalculator->calculate($items, $contract);

        return [
            'subtotal' => round((float) $full['subtotal'], 2),
            'discount' => round((float) $full['discount_value'], 2),
            'total' => round((float) $full['total'], 2),
        ];
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        ['items' => $contracts, 'total' => $total] = $this->contracts->paginateWithRelations($page, $perPage);

        // Cada contrato traz totais financeiros coerentes com seus itens e status
        $items = array_map(function (Contract $contract): array {
            $data = $contract->toArray();
            $data['calculation'] = $this->calculationForContract($data['items'] ?? [], $data);

            return $data;
        }, $contracts);

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $data): array
    {
        $client = null;
        if (!empty($data['client_id'])) {
            $client = $this->clients->find((int) $data['client_id']);
        }

        $header = ContractValidator::validatedHeaderForCreate($data, $client);

        $contract = $this->contracts->create([
            'client_id' => $header['client_id'],
            'start_date' => $header['start_date'],
            'end_date' => $header['end_date'],
            'status' => 'A',
        ]);

        // Itens do POST: validação + unit_value padrão = preço base do serviço se omitido
        foreach ($data['items'] as $item) {
            $service = $this->services->find((int) ($item['service_id'] ?? 0));
            $row = ContractItemValidator::validatedAttributesForContractCreate($item, $service);

            $this->contractItems->create([
                'contract_id' => (int) $contract->id,
                'service_id' => $row['service_id'],
                'quantity' => $row['quantity'],
                'unit_value' => $row['unit_value'],
            ]);
        }

        return $this->findById((int) $contract->id);
    }

    public function findById(int $id): array
    {
        $contract = $this->contracts->findWithRelations($id);
        if (!$contract) {
            throw new NotFoundException('Contrato nao encontrado.');
        }
        $data = $contract->toArray();
        $data['calculation'] = $this->calculationForContract($data['items'] ?? [], $data);

        return $data;
    }

    public function update(int $id, array $data): array
    {
        $contract = $this->requireContract($id);
        if ($contract->isCancelled()) {
            throw new BusinessException('Contrato cancelado nao pode ser editado.');
        }

        $updatable = ContractValidator::validatedUpdatePatch($data);

        if (!empty($updatable)) {
            $this->contracts->update($contract, $updatable);
        }

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $contract = $this->requireContract($id);
        $this->contracts->delete($contract);
    }

    public function cancel(int $id): array
    {
        $contract = $this->requireContract($id);
        if ($contract->isCancelled()) {
            throw new BusinessException('Contrato ja esta cancelado.');
        }

        $this->contracts->update($contract, ['status' => 'C']);

        return $this->findById($id);
    }

    public function addItem(int $contractId, array $data): array
    {
        $contract = $this->requireContract($contractId);
        if ($contract->isCancelled()) {
            throw new BusinessException('Contrato cancelado nao permite novos itens.');
        }

        $serviceId = (int) ($data['service_id'] ?? 0);
        $service = $this->services->find($serviceId);

        $row = ContractItemValidator::validatedAttributesForAppend($data, $service);

        $this->contractItems->create([
            'contract_id' => $contractId,
            'service_id' => $row['service_id'],
            'quantity' => $row['quantity'],
            'unit_value' => $row['unit_value'],
        ]);

        return $this->findById($contractId);
    }

    public function updateItem(int $contractId, int $itemId, array $data): array
    {
        $contract = $this->requireContract($contractId);
        if ($contract->isCancelled()) {
            throw new BusinessException('Contrato cancelado nao permite edicao de itens.');
        }

        $item = $this->contractItems->findByContractAndItemId($contractId, $itemId);
        if (!$item) {
            throw new NotFoundException('Item do contrato nao encontrado.');
        }

        $updatable = ContractItemValidator::validatedUpdatePatch($data);

        if (!empty($updatable)) {
            $this->contractItems->update($item, $updatable);
        }

        return $this->findById($contractId);
    }

    public function removeItem(int $contractId, int $itemId): array
    {
        $contract = $this->requireContract($contractId);
        if ($contract->isCancelled()) {
            throw new BusinessException('Contrato cancelado nao permite remocao de itens.');
        }

        $item = $this->contractItems->findByContractAndItemId($contractId, $itemId);
        if (!$item) {
            throw new NotFoundException('Item do contrato nao encontrado.');
        }

        $this->contractItems->delete($item);

        return $this->findById($contractId);
    }

    // find simples (sem relações) para mutações; detalhe completo via findById
    private function requireContract(int $id): Contract
    {
        $contract = $this->contracts->find($id);
        if (!$contract) {
            throw new NotFoundException('Contrato nao encontrado.');
        }

        return $contract;
    }
}
