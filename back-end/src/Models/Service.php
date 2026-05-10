<?php

declare(strict_types=1);

namespace App\Models;

// Catálogo: base_monthly_value exposto como string com duas casas em toArray
class Service
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $base_monthly_value = '0.00',
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: (string) ($row['name'] ?? ''),
            base_monthly_value: number_format((float) ($row['base_monthly_value'] ?? 0), 2, '.', ''),
            created_at: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updated_at: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_monthly_value' => $this->base_monthly_value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
