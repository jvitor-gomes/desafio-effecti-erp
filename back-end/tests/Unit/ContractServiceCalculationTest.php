<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\DiscountCalculator;
use App\Rules\DiscountRuleInterface;
use App\Services\ContractService;
use PHPUnit\Framework\TestCase;

class ContractServiceCalculationTest extends TestCase
{
    // Garante chaves discount/total esperadas pelo front a partir do motor
    public function test_maps_discount_engine_to_api_shape(): void
    {
        $stubRule = new class implements DiscountRuleInterface {
            public function isEnabled(): bool
            {
                return true;
            }

            public function calculate(float $subtotal, array $items, array $contract): array
            {
                return [
                    'discount_percent' => 0.0,
                    'discount_value' => 55.0,
                    'rule_name' => 'Stub',
                    'rule_description' => 'Teste',
                ];
            }
        };

        $inner = new DiscountCalculator([$stubRule]);
        $service = new ContractService($inner);

        $result = $service->calculationForContract([
            ['quantity' => 10, 'unit_value' => 100],
            ['quantity' => 2, 'unit_value' => 50],
        ], []);

        self::assertEquals(1100.0, $result['subtotal']);
        self::assertEquals(55.0, $result['discount']);
        self::assertEquals(1045.0, $result['total']);
    }
}
