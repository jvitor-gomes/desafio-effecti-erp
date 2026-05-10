<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    // Totais por linha e subtotal alinhados ao desafio de arredondamento
    public function test_line_total_example_challenge(): void
    {
        self::assertSame(200.0, Money::lineTotal(2, 100.0));
        self::assertSame(200.0, Money::lineTotal(1, 200.0));
        self::assertSame(400.0, Money::subtotalFromItems([
            ['quantity' => 2, 'unit_value' => 100.0],
            ['quantity' => 1, 'unit_value' => 200.0],
        ]));
    }

    public function test_subtotal_rounds_each_line(): void
    {
        self::assertSame(20.01, Money::subtotalFromItems([
            ['quantity' => 3, 'unit_value' => 6.666],
        ]));
    }
}
