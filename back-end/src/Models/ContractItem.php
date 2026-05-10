<?php

declare(strict_types=1);

namespace App\Models;

class ContractItem
{
    // Opcional: anexado ao hidratar itens do contrato com o serviço
    public ?Service $service = null;

    public function __construct(
        public ?int $id = null,
        public ?int $contract_id = null,
        public ?int $service_id = null,
        public int $quantity = 1,
        public string $unit_value = '0.00',
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            contract_id: isset($row['contract_id']) ? (int) $row['contract_id'] : null,
            service_id: isset($row['service_id']) ? (int) $row['service_id'] : null,
            quantity: (int) ($row['quantity'] ?? 1),
            unit_value: number_format((float) ($row['unit_value'] ?? 0), 2, '.', ''),
            created_at: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updated_at: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }

    public function toArray(): array
    {
        $base = [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'service_id' => $this->service_id,
            'quantity' => $this->quantity,
            'unit_value' => $this->unit_value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        if ($this->service !== null) {
            $base['service'] = $this->service->toArray();
        }

        return $base;
    }
}
