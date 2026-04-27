<?php

use Phinx\Migration\AbstractMigration;

final class CreateCustomersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('customers');

        $table
            ->addColumn('name', 'string')
            ->addColumn('email', 'string')
            ->addColumn('phone', 'string')
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
