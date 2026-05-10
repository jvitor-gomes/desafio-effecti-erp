<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveNonQuantityDiscountRules extends AbstractMigration
{
    public function up(): void
    {
        $this->query("DELETE FROM discount_rules WHERE type IS NULL OR type <> 'quantity'");
    }

    public function down(): void
    {
    }
}
