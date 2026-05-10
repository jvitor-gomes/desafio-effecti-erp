<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\Service;
use PDO;

class ServiceRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function paginate(int $page, int $perPage): array
    {
        $pdo = $this->pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM services ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = Service::fromRow($row)->toArray();
        }

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $attributes): Service
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO services (name, base_monthly_value, created_at, updated_at)
             VALUES (:name, :base_monthly_value, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING *'
        );
        $stmt->execute([
            'name' => $attributes['name'],
            'base_monthly_value' => $attributes['base_monthly_value'],
        ]);
        $row = $stmt->fetch() ?: [];

        return Service::fromRow($row);
    }

    public function find(int $id): ?Service
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? Service::fromRow($row) : null;
    }

    // PATCH parcial; sem campos alterados devolve o registro atual
    public function update(Service $service, array $payload): Service
    {
        $sets = [];
        $params = ['id' => $service->id];

        if (array_key_exists('name', $payload)) {
            $sets[] = 'name = :name';
            $params['name'] = $payload['name'];
        }
        if (array_key_exists('base_monthly_value', $payload)) {
            $sets[] = 'base_monthly_value = :base_monthly_value';
            $params['base_monthly_value'] = $payload['base_monthly_value'];
        }

        if ($sets === []) {
            return $this->find((int) $service->id) ?? $service;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE services SET ' . implode(', ', $sets) . ' WHERE id = :id RETURNING *';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return Service::fromRow($row);
    }

    public function delete(Service $service): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM services WHERE id = :id');
        $stmt->execute(['id' => $service->id]);
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}
