<?php

namespace App\Entities;

final class Customer
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public string $phone
    ) {}
}