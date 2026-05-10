<?php

declare(strict_types=1);

namespace App\Rules;

// Extensível: nova regra implementa calculate + isEnabled e entra no DiscountCalculator
interface DiscountRuleInterface
{
    public function calculate(float $subtotal, array $items, array $contract): array;

    public function isEnabled(): bool;
}
