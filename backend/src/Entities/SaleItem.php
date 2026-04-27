<?php

namespace App\Entities;

final class SaleItem
{
    public function __construct(
        public ?int $id,
        public int $saleId,
        public int $productId,
        public int $quantity,
        public string $unitPrice,
        public string $createdAt
    ) {}
}

