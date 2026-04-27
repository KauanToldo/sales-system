<?php

namespace App\Entities;

final class PaymentMethod
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $type,
        public bool $status
    ) {}
}