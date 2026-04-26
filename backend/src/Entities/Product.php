<?php

namespace App\Entities;

final class Product
{
    public function __construct(
        public ?int $id,
        public string $sku,
        public string $name,
        public string $category,
        public string $price
    ) {}
}