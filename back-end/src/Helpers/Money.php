<?php

declare(strict_types=1);

namespace App\Helpers;

class Money
{
    // Arredonda unitário antes de multiplicar; alinha totais com o motor de desconto
    public static function lineTotal(int $quantity, float $unitValue): float
    {
        if ($quantity < 1) {
            return 0.0;
        }

        $unitRounded = round($unitValue, 2);

        return round($quantity * $unitRounded, 2);
    }

    // Soma os totais por linha (cada linha já com duas casas decimais)
    public static function subtotalFromItems(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_value'] ?? 0);
            $sum += self::lineTotal($qty, $unit);
        }

        return round($sum, 2);
    }
}
