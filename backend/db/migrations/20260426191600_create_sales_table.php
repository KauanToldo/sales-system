<?php

use Phinx\Migration\AbstractMigration;

final class CreateSalesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sales');

        $table
            ->addColumn('customer_id', 'integer', ['null' => true])
            ->addColumn('user_id', 'integer')
            ->addColumn('total', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('total_paid', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('change_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('status', 'string', ['default' => 'OPEN'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['customer_id'])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('customer_id', 'customers', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->save();
    }
}

