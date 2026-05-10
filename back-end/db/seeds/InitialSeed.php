<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class InitialSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $clients = [
            [
                'name' => 'Suprilog Distribuidora Ltda',
                'document' => $this->generateValidCnpj(601_001),
                'email' => 'licitacoes@suprilog.demo',
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Construtora Andrade Engenharia S.A.',
                'document' => $this->generateValidCnpj(601_002),
                'email' => 'editais@andradeengenharia.demo',
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'MedSul Equipamentos Hospitalares Ltda',
                'document' => $this->generateValidCnpj(601_003),
                'email' => 'pregoes@medsul.demo',
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TechGov Soluções em TI Ltda',
                'document' => $this->generateValidCnpj(601_004),
                'email' => 'comercial@techgov.demo',
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Papelaria Bandeirantes ME (inativa)',
                'document' => $this->generateValidCnpj(601_005),
                'email' => 'contato@bandeirantes.demo',
                'status' => 'I',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $services = [
            ['name' => 'Monitor de Editais — Plano Essencial', 'base_monthly_value' => 149.00, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Monitor de Editais — Plano Pro', 'base_monthly_value' => 449.00, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Robô de captura — portais estaduais', 'base_monthly_value' => 199.00, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Licença adicional de usuário licitante', 'base_monthly_value' => 39.00, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Geração automatizada de propostas', 'base_monthly_value' => 320.00, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Treinamento em pregão eletrônico (sessão)', 'base_monthly_value' => 280.00, 'created_at' => $now, 'updated_at' => $now],
        ];

        $discountRules = [
            ['name' => 'Volume 3+ licenças — 2%', 'type' => 'quantity', 'min_quantity' => 3, 'value_type' => 'percent', 'value' => 2.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Volume 5+ licenças — 3%', 'type' => 'quantity', 'min_quantity' => 5, 'value_type' => 'percent', 'value' => 3.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Volume 10+ licenças — 5%', 'type' => 'quantity', 'min_quantity' => 10, 'value_type' => 'percent', 'value' => 5.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Promoção lançamento (inativa)', 'type' => 'quantity', 'min_quantity' => 8, 'value_type' => 'percent', 'value' => 8.00, 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->table('clients')->insert($clients)->saveData();
        $this->table('services')->insert($services)->saveData();
        $this->table('discount_rules')->insert($discountRules)->saveData();

        $contracts = [
            [
                'client_id' => 1,
                'start_date' => '2024-02-01',
                'end_date' => null,
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'client_id' => 2,
                'start_date' => '2024-03-15',
                'end_date' => '2025-12-31',
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'client_id' => 3,
                'start_date' => '2024-06-01',
                'end_date' => null,
                'status' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'client_id' => 4,
                'start_date' => '2023-09-01',
                'end_date' => null,
                'status' => 'C',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->table('contracts')->insert($contracts)->saveData();

        $items = [
            ['contract_id' => 1, 'service_id' => 1, 'quantity' => 2, 'unit_value' => 149.00, 'created_at' => $now, 'updated_at' => $now],
            ['contract_id' => 1, 'service_id' => 6, 'quantity' => 1, 'unit_value' => 280.00, 'created_at' => $now, 'updated_at' => $now],
            ['contract_id' => 2, 'service_id' => 4, 'quantity' => 12, 'unit_value' => 39.00, 'created_at' => $now, 'updated_at' => $now],
            ['contract_id' => 3, 'service_id' => 3, 'quantity' => 6, 'unit_value' => 199.00, 'created_at' => $now, 'updated_at' => $now],
            ['contract_id' => 3, 'service_id' => 4, 'quantity' => 4, 'unit_value' => 39.00, 'created_at' => $now, 'updated_at' => $now],
            ['contract_id' => 4, 'service_id' => 2, 'quantity' => 10, 'unit_value' => 449.00, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->table('contract_items')->insert($items)->saveData();
    }

    private function generateValidCnpj(int $seed): string
    {
        $base = str_pad((string) ($seed % 1_000_000_000_000), 12, '0', STR_PAD_LEFT);

        if (preg_match('/^(\d)\1{11}$/', $base)) {
            $base = str_pad((string) (($seed + 137_891) % 1_000_000_000_000), 12, '0', STR_PAD_LEFT);
        }

        $cnpj = $base;

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        $cnpj .= (string) $digit1;

        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        $cnpj .= (string) $digit2;

        return $cnpj;
    }
}
