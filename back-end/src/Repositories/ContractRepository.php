<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use PDO;

class ContractRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function paginateWithRelations(int $page, int $perPage): array
    {
        $pdo = $this->pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM contracts ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return ['items' => $this->hydrateContractsWithRelations($rows), 'total' => $total];
    }

    public function findWithRelations(int $id): ?Contract
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM contracts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $attached = $this->hydrateContractsWithRelations([$row]);

        return $attached[0] ?? null;
    }

    public function find(int $id): ?Contract
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM contracts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? Contract::fromRow($row) : null;
    }

    public function create(array $attributes): Contract
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO contracts (client_id, start_date, end_date, status, created_at, updated_at)
             VALUES (:client_id, :start_date, :end_date, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING *'
        );
        $stmt->execute([
            'client_id' => $attributes['client_id'],
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'status' => $attributes['status'] ?? 'A',
        ]);
        $row = $stmt->fetch() ?: [];

        return Contract::fromRow($row);
    }

    // PATCH parcial de cabeçalho do contrato
    public function update(Contract $contract, array $payload): void
    {
        $sets = [];
        $params = ['id' => $contract->id];

        if (array_key_exists('start_date', $payload)) {
            $sets[] = 'start_date = :start_date';
            $params['start_date'] = $payload['start_date'];
        }
        if (array_key_exists('end_date', $payload)) {
            $sets[] = 'end_date = :end_date';
            $params['end_date'] = $payload['end_date'];
        }
        if (array_key_exists('status', $payload)) {
            $sets[] = 'status = :status';
            $params['status'] = $payload['status'];
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE contracts SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(Contract $contract): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM contracts WHERE id = :id');
        $stmt->execute(['id' => $contract->id]);
    }

    public function existsForClientId(int $clientId): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM contracts WHERE client_id = :cid'
        );
        $stmt->execute(['cid' => $clientId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    // Poucas queries IN (...) em vez de um SELECT por contrato (N+1)
    private function hydrateContractsWithRelations(array $contractRows): array
    {
        if ($contractRows === []) {
            return [];
        }

        $contractIds = [];
        $clientIds = [];
        foreach ($contractRows as $r) {
            $contractIds[] = (int) $r['id'];
            $clientIds[] = (int) $r['client_id'];
        }
        $clientIds = array_unique($clientIds);

        $clients = $this->fetchClientsByIds($clientIds);
        $itemsByContract = $this->fetchContractItemRowsGroupedByContractId($contractIds);

        $serviceIds = [];
        foreach ($itemsByContract as $items) {
            foreach ($items as $ir) {
                $serviceIds[] = (int) $ir['service_id'];
            }
        }
        $serviceIds = array_unique($serviceIds);
        $services = $this->fetchServicesByIds($serviceIds);

        $result = [];
        foreach ($contractRows as $row) {
            $contract = Contract::fromRow($row);
            $cid = (int) $row['client_id'];
            $contract->client = $clients[$cid] ?? null;

            $itemRows = $itemsByContract[(int) $row['id']] ?? [];
            $items = [];
            foreach ($itemRows as $ir) {
                $ci = ContractItem::fromRow($ir);
                $sid = (int) $ir['service_id'];
                $ci->service = $services[$sid] ?? null;
                $items[] = $ci;
            }
            $contract->items = $items;
            $result[] = $contract;
        }

        return $result;
    }

    private function fetchClientsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare("SELECT * FROM clients WHERE id IN ($placeholders)");
        $stmt->execute(array_values($ids));

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $c = Client::fromRow($row);
            if ($c->id !== null) {
                $map[$c->id] = $c;
            }
        }

        return $map;
    }

    private function fetchContractItemRowsGroupedByContractId(array $contractIds): array
    {
        if ($contractIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($contractIds), '?'));
        $sql = "SELECT * FROM contract_items WHERE contract_id IN ($placeholders) ORDER BY contract_id ASC, id ASC";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array_values($contractIds));

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $cid = (int) $row['contract_id'];
            $map[$cid] ??= [];
            $map[$cid][] = $row;
        }

        return $map;
    }

    private function fetchServicesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare("SELECT * FROM services WHERE id IN ($placeholders)");
        $stmt->execute(array_values($ids));

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $s = Service::fromRow($row);
            if ($s->id !== null) {
                $map[$s->id] = $s;
            }
        }

        return $map;
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}
