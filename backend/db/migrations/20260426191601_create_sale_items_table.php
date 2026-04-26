<?php

use Phinx\Migration\AbstractMigration;

final class CreateSaleItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sale_items');

        $table
            ->addColumn('sale_id', 'integer')
            ->addColumn('product_id', 'integer')
            ->addColumn('quantity', 'integer')
            ->addColumn('unit_price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['sale_id'])
            ->addIndex(['product_id'])
            ->create();

        $table
            ->addForeignKey('sale_id', 'sales', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->save();
    }
}

