<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContractItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('contract_items')
            ->addColumn('contract_id', 'integer')
            ->addColumn('service_id', 'integer')
            ->addColumn('quantity', 'integer', ['default' => 1])
            ->addColumn('unit_value', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addTimestamps()
            ->addForeignKey('contract_id', 'contracts', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('service_id', 'services', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
