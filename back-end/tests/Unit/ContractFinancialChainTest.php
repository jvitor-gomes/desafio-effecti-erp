<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\DiscountCalculator;
use App\Rules\DiscountRuleInterface;
use App\Rules\QuantityDiscountRule;
use App\Models\DiscountRule;
use App\Repositories\DiscountRuleRepository;
use App\Services\ContractService;
use PHPUnit\Framework\TestCase;

class ContractFinancialChainTest extends TestCase
{
    // Sem regras ativas o desconto é zero; cancelado ignora desconto mesmo com regras
    public function test_api_totals_match_subtotal_without_rules(): void
    {
        $repo = $this->createMock(DiscountRuleRepository::class);
        $repo->method('findAllActiveQuantityRulesOrdered')->willReturn([]);

        $service = new ContractService(new DiscountCalculator([new QuantityDiscountRule($repo)]));
        $out = $service->calculationForContract([
            ['quantity' => 2, 'unit_value' => 100.0],
            ['quantity' => 1, 'unit_value' => 200.0],
        ], ['status' => 'A']);

        self::assertSame(400.0, $out['subtotal']);
        self::assertSame(0.0, $out['discount']);
        self::assertSame(400.0, $out['total']);
    }

    public function test_cancelled_contract_skips_discount_in_api_totals(): void
    {
        $r = new DiscountRule(
            name: '10+ 5%',
            type: 'quantity',
            min_quantity: 1,
            value_type: 'percent',
            value: 50.0,
            is_active: true,
        );

        $repo = $this->createMock(DiscountRuleRepository::class);
        $repo->method('findAllActiveQuantityRulesOrdered')->willReturn([$r]);

        $service = new ContractService(new DiscountCalculator([new QuantityDiscountRule($repo)]));
        $out = $service->calculationForContract(
            [['quantity' => 10, 'unit_value' => 100.0]],
            ['status' => 'C']
        );

        self::assertSame(1000.0, $out['subtotal']);
        self::assertSame(0.0, $out['discount']);
        self::assertSame(1000.0, $out['total']);
    }

    public function test_line_discount_never_exceeds_line_total_with_stacked_fixed_rules(): void
    {
        $a = new DiscountRule(name: 'A', type: 'quantity', min_quantity: 1, value_type: 'fixed', value: 400.0, is_active: true);
        $b = new DiscountRule(name: 'B', type: 'quantity', min_quantity: 1, value_type: 'fixed', value: 300.0, is_active: true);

        $repo = $this->createMock(DiscountRuleRepository::class);
        $repo->method('findAllActiveQuantityRulesOrdered')->willReturn([$a, $b]);

        $calculator = new DiscountCalculator([new QuantityDiscountRule($repo)]);
        $out = $calculator->calculate([['quantity' => 1, 'unit_value' => 500.0]], ['status' => 'A']);

        self::assertSame(500.0, $out['subtotal']);
        self::assertSame(500.0, $out['discount_value']);
        self::assertSame(0.0, $out['total']);
    }

    public function test_discount_total_capped_to_subtotal(): void
    {
        $rule = new class implements DiscountRuleInterface {
            public function isEnabled(): bool
            {
                return true;
            }

            public function calculate(float $subtotal, array $items, array $contract): array
            {
                return [
                    'discount_percent' => 0.0,
                    'discount_value' => $subtotal + 100.0,
                    'rule_name' => 'Test',
                    'rule_description' => 'overflow',
                ];
            }
        };

        $out = (new DiscountCalculator([$rule]))->calculate([['quantity' => 1, 'unit_value' => 10.0]], []);
        self::assertSame(10.0, $out['subtotal']);
        self::assertSame(10.0, $out['discount_value']);
        self::assertSame(0.0, $out['total']);
    }
}
