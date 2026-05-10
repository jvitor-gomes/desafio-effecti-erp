<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\ContractItem;
use PDO;

class ContractItemRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function create(array $attributes): ContractItem
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO contract_items (contract_id, service_id, quantity, unit_value, created_at, updated_at)
             VALUES (:contract_id, :service_id, :quantity, :unit_value, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING *'
        );
        $stmt->execute([
            'contract_id' => $attributes['contract_id'],
            'service_id' => $attributes['service_id'],
            'quantity' => $attributes['quantity'],
            'unit_value' => $attributes['unit_value'],
        ]);
        $row = $stmt->fetch() ?: [];

        return ContractItem::fromRow($row);
    }

    public function findByContractAndItemId(int $contractId, int $itemId): ?ContractItem
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM contract_items WHERE contract_id = :cid AND id = :iid LIMIT 1'
        );
        $stmt->execute(['cid' => $contractId, 'iid' => $itemId]);
        $row = $stmt->fetch();

        return $row !== false ? ContractItem::fromRow($row) : null;
    }

    // PATCH parcial de quantidade e/ou unit_value
    public function update(ContractItem $item, array $payload): void
    {
        $sets = [];
        $params = ['id' => $item->id];

        if (array_key_exists('quantity', $payload)) {
            $sets[] = 'quantity = :quantity';
            $params['quantity'] = $payload['quantity'];
        }
        if (array_key_exists('unit_value', $payload)) {
            $sets[] = 'unit_value = :unit_value';
            $params['unit_value'] = $payload['unit_value'];
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE contract_items SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(ContractItem $item): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM contract_items WHERE id = :id');
        $stmt->execute(['id' => $item->id]);
    }

    public function existsForServiceId(int $serviceId): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM contract_items WHERE service_id = :sid'
        );
        $stmt->execute(['sid' => $serviceId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}
