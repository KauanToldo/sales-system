<?php

use Phinx\Migration\AbstractMigration;

final class CreateSalePaymentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('payments');

        $table
            ->addColumn('sale_id', 'integer')
            ->addColumn('payment_method_id', 'integer')
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['sale_id'])
            ->addIndex(['payment_method_id'])
            ->create();

        $table
            ->addForeignKey('sale_id', 'sales', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('payment_method_id', 'payment_methods', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->save();
    }
}

