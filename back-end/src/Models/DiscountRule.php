<?php

declare(strict_types=1);

namespace App\Models;

class DiscountRule
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $type = 'quantity',
        public int $min_quantity = 1,
        public string $value_type = 'percent',
        public float $value = 0.0,
        public bool $is_active = true,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: (string) ($row['name'] ?? ''),
            type: (string) ($row['type'] ?? 'quantity'),
            min_quantity: (int) ($row['min_quantity'] ?? 1),
            value_type: (string) ($row['value_type'] ?? 'percent'),
            value: (float) ($row['value'] ?? 0),
            is_active: self::normalizeBool($row['is_active'] ?? true),
            created_at: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updated_at: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'min_quantity' => $this->min_quantity,
            'value_type' => $this->value_type,
            'value' => $this->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    // PDO/Postgres podem devolver boolean como string ou inteiro
    private static function normalizeBool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if ($v === null) {
            return false;
        }
        if (is_int($v)) {
            return $v === 1;
        }

        $s = strtolower(trim((string) $v));

        return $s === 't' || $s === 'true' || $s === '1' || $s === 'yes' || $s === 'on';
    }
}
