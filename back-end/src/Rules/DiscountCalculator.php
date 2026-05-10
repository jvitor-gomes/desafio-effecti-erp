<?php

declare(strict_types=1);

namespace App\Rules;

use App\Helpers\Money;

class DiscountCalculator
{
    private array $rules;

    // $rules null = produção; array fixo injeta regras mockadas nos testes
    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? [
            new QuantityDiscountRule(),
        ];
    }

    public function calculate(array $items, array $contract = []): array
    {
        $subtotal = Money::subtotalFromItems($items);
        $totalDiscountPercent = 0.0;
        $totalDiscountValue = 0.0;
        $appliedRules = [];

        foreach ($this->rules as $rule) {
            if (!$rule->isEnabled()) {
                continue;
            }

            $result = $rule->calculate($subtotal, $items, $contract);

            if ($result['discount_value'] > 0) {
                $totalDiscountPercent += $result['discount_percent'];
                $totalDiscountValue += $result['discount_value'];
                $appliedRules[] = [
                    'rule_name' => $result['rule_name'],
                    'rule_description' => $result['rule_description'],
                    'discount_percent' => $result['discount_percent'],
                    'discount_value' => $result['discount_value'],
                ];
            }
        }

        // Desconto não ultrapassa o subtotal; total com duas casas decimais
        $totalDiscountValue = round($totalDiscountValue, 2);
        $totalDiscountValue = min($totalDiscountValue, $subtotal);
        $total = round($subtotal - $totalDiscountValue, 2);
        if ($total < 0) {
            $total = 0.0;
        }

        return [
            'subtotal' => $subtotal,
            'discount_percent' => $totalDiscountPercent,
            'discount_value' => $totalDiscountValue,
            'total' => $total,
            'applied_rules' => $appliedRules,
        ];
    }
}
