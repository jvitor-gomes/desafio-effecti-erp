<?php

declare(strict_types=1);

namespace App\Models;

// Linha da tabela clients; fromRow normaliza tipos vindos do PDO
class Client
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $document = '',
        public string $email = '',
        public string $status = 'A',
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            name: (string) ($row['name'] ?? ''),
            document: (string) ($row['document'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            status: (string) ($row['status'] ?? 'A'),
            created_at: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updated_at: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document' => $this->document,
            'email' => $this->email,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
