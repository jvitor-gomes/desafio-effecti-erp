<?php

declare(strict_types=1);

namespace App\Models;

class Contract
{
    // Preenchidos pelo ContractRepository ao listar/detalhar com relações
    public ?Client $client = null;

    public ?array $items = null;

    public function __construct(
        public ?int $id = null,
        public ?int $client_id = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public string $status = 'A',
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            client_id: isset($row['client_id']) ? (int) $row['client_id'] : null,
            start_date: self::formatDate($row['start_date'] ?? null),
            end_date: self::formatDate($row['end_date'] ?? null),
            status: (string) ($row['status'] ?? 'A'),
            created_at: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updated_at: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    public function isCancelled(): bool
    {
        return $this->status === 'C';
    }

    public function toArray(): array
    {
        $base = [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        if ($this->client !== null) {
            $base['client'] = $this->client->toArray();
        }
        if ($this->items !== null) {
            $base['items'] = array_map(static fn (ContractItem $i) => $i->toArray(), $this->items);
        }

        return $base;
    }

    // Datas vindas do Postgres podem incluir hora; API expõe só Y-m-d
    private static function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = (string) $value;

        return substr($s, 0, 10);
    }
}
