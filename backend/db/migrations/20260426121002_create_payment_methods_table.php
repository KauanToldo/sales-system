<?php

use Phinx\Migration\AbstractMigration;

final class CreatePaymentMethodsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('payment_methods');

        $table
            ->addColumn('name', 'string')
            ->addColumn('type', 'string')
            ->addColumn('status', 'boolean', ['default' => true])
            ->create();
    }
}
