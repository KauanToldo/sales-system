<?php

use Phinx\Seed\AbstractSeed;

class PaymentMethodSeeder extends AbstractSeed
{
    public function run(): void
    {
        $existing = $this->fetchRow('SELECT COUNT(*) AS total FROM payment_methods');
        if ((int) ($existing['total'] ?? 0) > 0) {
            return;
        }

        $methods = [
            [
                'name' => 'Debit',
                'type' => 'ELECTRONIC',
                'status' => true,
            ],
            [
                'name' => 'Credit',
                'type' => 'ELECTRONIC',
                'status' => true,
            ],
            [
                'name' => 'PIX',
                'type' => 'ELECTRONIC',
                'status' => true,
            ],
            [
                'name' => 'Cash',
                'type' => 'CASH',
                'status' => true,
            ],
        ];

        $this->table('payment_methods')->insert($methods)->saveData();
    }
}
