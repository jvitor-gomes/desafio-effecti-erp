<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\DiscountCalculator;
use App\Rules\QuantityDiscountRule;
use App\Models\DiscountRule;
use App\Repositories\DiscountRuleRepository;
use PHPUnit\Framework\TestCase;

class QuantityDiscountRuleTest extends TestCase
{
    // Regra com repositório mockado e calculadora ponta a ponta
    public function test_is_enabled_when_repository_has_active_rules(): void
    {
        $rule = new DiscountRule(type: 'quantity', min_quantity: 1, value_type: 'percent', value: 1.0, is_active: true);

        $repo = $this->createMock(DiscountRuleRepository::class);
        $repo->method('findAllActiveQuantityRulesOrdered')->willReturn([$rule]);

        $q = new QuantityDiscountRule($repo);
        self::assertTrue($q->isEnabled());
    }

    public function test_discount_calculator_applies_quantity_rule_end_to_end(): void
    {
        $r = new DiscountRule(
            name: '10+ 5%',
            type: 'quantity',
            min_quantity: 10,
            value_type: 'percent',
            value: 5.0,
            is_active: true,
        );

        $repo = $this->createMock(DiscountRuleRepository::class);
        $repo->method('findAllActiveQuantityRulesOrdered')->willReturn([$r]);

        $calculator = new DiscountCalculator([new QuantityDiscountRule($repo)]);
        $out = $calculator->calculate([['quantity' => 10, 'unit_value' => 100.0]], []);

        self::assertEquals(1000.0, $out['subtotal']);
        self::assertEqualsWithDelta(50.0, $out['discount_value'], 0.001);
        self::assertEqualsWithDelta(950.0, $out['total'], 0.001);
        self::assertNotEmpty($out['applied_rules']);
    }
}
