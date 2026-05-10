<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDiscountRulesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('discount_rules')
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('type', 'string', ['limit' => 50])
            ->addColumn('min_quantity', 'integer', ['default' => 1])
            ->addColumn('value_type', 'string', ['limit' => 20, 'default' => 'percent'])
            ->addColumn('value', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addTimestamps()
            ->create();
    }
}
