<?php

use Phinx\Migration\AbstractMigration;

final class CreateProductsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('products');

        $table
            ->addColumn('sku', 'string')
            ->addColumn('name', 'string')
            ->addColumn('category', 'string')
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addIndex(['sku'], ['unique' => true])
            ->create();
    }
}
