<?php

namespace App\Repositories;

use App\Entities\Payment;
use PDO;

final class PaymentRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(int $saleId, int $paymentMethodId, string $amount): Payment
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO payments (sale_id, payment_method_id, amount)
            VALUES (?, ?, ?)
            RETURNING id, sale_id, payment_method_id, amount, created_at
        ");

        $stmt->execute([$saleId, $paymentMethodId, $amount]);
        $data = $stmt->fetch();

        return new Payment(
            (int) $data['id'],
            (int) $data['sale_id'],
            (int) $data['payment_method_id'],
            (string) $data['amount'],
            (string) $data['created_at']
        );
    }

    public function listBySaleId(int $saleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, sale_id, payment_method_id, amount, created_at
            FROM payments
            WHERE sale_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$saleId]);

        $payments = [];
        while ($data = $stmt->fetch()) {
            $payments[] = new Payment(
                (int) $data['id'],
                (int) $data['sale_id'],
                (int) $data['payment_method_id'],
                (string) $data['amount'],
                (string) $data['created_at']
            );
        }

        return $payments;
    }
}

