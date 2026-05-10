<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Models\DiscountRule;
use PDO;

class DiscountRuleRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function paginate(int $page, int $perPage): array
    {
        $pdo = $this->connection();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM discount_rules')->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT * FROM discount_rules ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = DiscountRule::fromRow($row)->toArray();
        }

        return ['items' => $items, 'total' => $total];
    }

    // Ordem crescente por min_quantity para avaliar faixas na regra de desconto
    public function findAllActiveQuantityRulesOrdered(): array
    {
        $stmt = $this->connection()->prepare(
            'SELECT * FROM discount_rules
             WHERE is_active = TRUE AND type = :type
             ORDER BY min_quantity ASC'
        );
        $stmt->execute(['type' => 'quantity']);

        $list = [];
        foreach ($stmt->fetchAll() as $row) {
            $list[] = DiscountRule::fromRow($row);
        }

        return $list;
    }

    public function create(array $attributes): DiscountRule
    {
        $stmt = $this->connection()->prepare(
            'INSERT INTO discount_rules (name, type, min_quantity, value_type, value, is_active, created_at, updated_at)
             VALUES (:name, :type, :min_quantity, :value_type, :value, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING *'
        );
        $this->bindAndExecute($stmt, [
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'min_quantity' => $attributes['min_quantity'],
            'value_type' => $attributes['value_type'],
            'value' => $attributes['value'],
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);
        $row = $stmt->fetch() ?: [];

        return DiscountRule::fromRow($row);
    }

    public function find(int $id): ?DiscountRule
    {
        $stmt = $this->connection()->prepare('SELECT * FROM discount_rules WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? DiscountRule::fromRow($row) : null;
    }

    public function update(DiscountRule $rule, array $payload): DiscountRule
    {
        $sets = [];
        $params = ['id' => $rule->id];

        if (array_key_exists('name', $payload)) {
            $sets[] = 'name = :name';
            $params['name'] = $payload['name'];
        }
        if (array_key_exists('type', $payload)) {
            $sets[] = 'type = :type';
            $params['type'] = $payload['type'];
        }
        if (array_key_exists('min_quantity', $payload)) {
            $sets[] = 'min_quantity = :min_quantity';
            $params['min_quantity'] = $payload['min_quantity'];
        }
        if (array_key_exists('value_type', $payload)) {
            $sets[] = 'value_type = :value_type';
            $params['value_type'] = $payload['value_type'];
        }
        if (array_key_exists('value', $payload)) {
            $sets[] = 'value = :value';
            $params['value'] = $payload['value'];
        }
        if (array_key_exists('is_active', $payload)) {
            $sets[] = 'is_active = :is_active';
            $params['is_active'] = (bool) $payload['is_active'];
        }

        if ($sets === []) {
            return $this->find((int) $rule->id) ?? $rule;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE discount_rules SET ' . implode(', ', $sets) . ' WHERE id = :id RETURNING *';
        $stmt = $this->connection()->prepare($sql);
        $this->bindAndExecute($stmt, $params);
        $row = $stmt->fetch() ?: [];

        return DiscountRule::fromRow($row);
    }

    public function delete(DiscountRule $rule): void
    {
        $stmt = $this->connection()->prepare('DELETE FROM discount_rules WHERE id = :id');
        $stmt->execute(['id' => $rule->id]);
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }

    // bindValue com tipo em bool/int evita false virar string vazia no Postgres
    private function bindAndExecute(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $placeholder = ':' . ltrim((string) $key, ':');
            if (is_bool($value)) {
                $stmt->bindValue($placeholder, $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($placeholder, $value);
            }
        }
        $stmt->execute();
    }
}
