<?php

declare(strict_types=1);

namespace App\Rules;

use App\Helpers\Money;
use App\Repositories\DiscountRuleRepository;

class QuantityDiscountRule implements DiscountRuleInterface
{
    public function __construct(
        private readonly DiscountRuleRepository $discountRules = new DiscountRuleRepository(),
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->discountRules->findAllActiveQuantityRulesOrdered() !== [];
    }

    // Por item: empilha descontos das regras ativas cuja quantidade atinge min_quantity
    public function calculate(float $subtotal, array $items, array $contract): array
    {
        $result = [
            'discount_percent' => 0.0,
            'discount_value' => 0.0,
            'rule_name' => 'Desconto por quantidade',
            'rule_description' => 'Nenhum desconto aplicado.',
        ];

        // Contrato cancelado: mantém subtotal na API, mas não aplica desconto
        if (($contract['status'] ?? 'A') === 'C') {
            $result['rule_description'] = 'Contrato cancelado: desconto por quantidade nao aplicado.';

            return $result;
        }

        $rules = $this->discountRules->findAllActiveQuantityRulesOrdered();
        if ($rules === [] || $subtotal <= 0) {
            return $result;
        }

        $discount = 0.0;
        $appliedParts = [];

        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $itemTotal = Money::lineTotal($qty, (float) ($item['unit_value'] ?? 0));
            if ($itemTotal <= 0) {
                continue;
            }

            $lineDiscount = 0.0;
            foreach ($rules as $rule) {
                if ($qty < $rule->min_quantity) {
                    continue;
                }

                $lineDiscount += $this->lineDiscountAmount($itemTotal, $rule->value, $rule->value_type);
                $appliedParts[] = sprintf(
                    '%s: %s na linha (qtd >= %d)',
                    $rule->name,
                    $rule->value_type === 'percent'
                        ? sprintf('%.2f%%', $rule->value)
                        : sprintf('R$ %.2f fixo', $rule->value),
                    $rule->min_quantity
                );
            }

            $lineDiscount = round(min($lineDiscount, $itemTotal), 2);
            $discount += $lineDiscount;
        }

        $discount = round($discount, 2);
        if ($discount <= 0) {
            return $result;
        }

        $result['discount_value'] = $discount;
        if ($subtotal > 0) {
            $result['discount_percent'] = round(($discount / $subtotal) * 100, 2);
        }
        $result['rule_description'] = implode('; ', $appliedParts) ?: 'Desconto por quantidade aplicado.';

        return $result;
    }

    private function lineDiscountAmount(float $lineTotal, float $ruleValue, string $valueType): float
    {
        $base = round($lineTotal, 2);
        if ($base <= 0) {
            return 0.0;
        }

        if ($valueType === 'fixed') {
            return round(min(round($ruleValue, 2), $base), 2);
        }

        return round($base * (round($ruleValue, 2) / 100.0), 2);
    }
}
