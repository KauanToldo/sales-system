<?php

namespace App\Entities;

final class Payment
{
    public function __construct(
        public ?int $id,
        public int $saleId,
        public int $paymentMethodId,
        public string $amount,
        public string $createdAt
    ) {}
}

