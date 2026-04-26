<?php

namespace App\Entities;

final class Sale
{
    /**
     * @param SaleItem[] $items
     * @param Payment[] $payments
     */
    public function __construct(
        public ?int $id,
        public ?int $customerId,
        public int $userId,
        public string $total,
        public string $totalPaid,
        public string $changeAmount,
        public string $status,
        public string $createdAt,
        public array $items = [],
        public array $payments = []
    ) {}
}

