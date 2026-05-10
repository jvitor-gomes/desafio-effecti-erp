<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateServicesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('services')
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('base_monthly_value', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addTimestamps()
            ->create();
    }
}
