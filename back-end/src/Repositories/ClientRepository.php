<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\Client;
use PDO;

class ClientRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function paginate(int $page, int $perPage): array
    {
        $pdo = $this->pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM clients ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = Client::fromRow($row)->toArray();
        }

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $attributes): Client
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO clients (name, document, email, status, created_at, updated_at)
             VALUES (:name, :document, :email, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING *'
        );
        $stmt->execute([
            'name' => $attributes['name'],
            'document' => $attributes['document'],
            'email' => $attributes['email'],
            'status' => $attributes['status'],
        ]);
        $row = $stmt->fetch() ?: [];

        return Client::fromRow($row);
    }

    public function find(int $id): ?Client
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? Client::fromRow($row) : null;
    }

    // PATCH parcial: só colunas presentes em $payload entram no UPDATE
    public function update(Client $client, array $payload): Client
    {
        $sets = [];
        $params = ['id' => $client->id];

        if (array_key_exists('name', $payload)) {
            $sets[] = 'name = :name';
            $params['name'] = $payload['name'];
        }
        if (array_key_exists('email', $payload)) {
            $sets[] = 'email = :email';
            $params['email'] = $payload['email'];
        }
        if (array_key_exists('document', $payload)) {
            $sets[] = 'document = :document';
            $params['document'] = $payload['document'];
        }
        if (array_key_exists('status', $payload)) {
            $sets[] = 'status = :status';
            $params['status'] = $payload['status'];
        }

        if ($sets === []) {
            return $this->find((int) $client->id) ?? $client;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE clients SET ' . implode(', ', $sets) . ' WHERE id = :id RETURNING *';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return Client::fromRow($row);
    }

    public function delete(Client $client): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM clients WHERE id = :id');
        $stmt->execute(['id' => $client->id]);
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}
