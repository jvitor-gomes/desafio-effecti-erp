<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContractsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('contracts')
            ->addColumn('client_id', 'integer')
            ->addColumn('start_date', 'date')
            ->addColumn('end_date', 'date', ['null' => true])
            ->addColumn('status', 'char', ['limit' => 1, 'default' => 'A'])
            ->addTimestamps()
            ->addForeignKey('client_id', 'clients', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->create();
    }
}
