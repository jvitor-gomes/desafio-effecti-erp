<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateClientsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('clients')
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('document', 'string', ['limit' => 14])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('status', 'char', ['limit' => 1, 'default' => 'A'])
            ->addTimestamps()
            ->addIndex(['document'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
