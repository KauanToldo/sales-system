<?php

namespace App\Repositories;

use App\Entities\Sale;
use PDO;

final class SaleRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(?int $customerId, int $userId, string $total, string $totalPaid, string $changeAmount, string $status): Sale
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sales (customer_id, user_id, total, total_paid, change_amount, status)
            VALUES (?, ?, ?, ?, ?, ?)
            RETURNING id, customer_id, user_id, total, total_paid, change_amount, status, created_at
        ");

        $stmt->execute([
            $customerId,
            $userId,
            $total,
            $totalPaid,
            $changeAmount,
            $status,
        ]);

        $data = $stmt->fetch();

        return new Sale(
            (int) $data['id'],
            $data['customer_id'] !== null ? (int) $data['customer_id'] : null,
            (int) $data['user_id'],
            (string) $data['total'],
            (string) $data['total_paid'],
            (string) $data['change_amount'],
            (string) $data['status'],
            (string) $data['created_at']
        );
    }

    public function updateTotals(int $saleId, string $total, string $totalPaid, string $changeAmount): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE sales
            SET total = ?, total_paid = ?, change_amount = ?
            WHERE id = ?
        ");

        $stmt->execute([$total, $totalPaid, $changeAmount, $saleId]);
    }

    public function updateStatus(int $saleId, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE sales
            SET status = ?
            WHERE id = ?
        ");

        $stmt->execute([$status, $saleId]);
    }

    public function list(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, customer_id, user_id, total, total_paid, change_amount, status, created_at
            FROM sales
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);

        $sales = [];
        while ($data = $stmt->fetch()) {
            $sales[] = new Sale(
                (int) $data['id'],
                $data['customer_id'] !== null ? (int) $data['customer_id'] : null,
                (int) $data['user_id'],
                (string) $data['total'],
                (string) $data['total_paid'],
                (string) $data['change_amount'],
                (string) $data['status'],
                (string) $data['created_at']
            );
        }

        return $sales;
    }

    public function findById(int $id): ?Sale
    {
        $stmt = $this->pdo->prepare("
            SELECT id, customer_id, user_id, total, total_paid, change_amount, status, created_at
            FROM sales
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $data = $stmt->fetch();
        if (!$data) return null;

        return new Sale(
            (int) $data['id'],
            $data['customer_id'] !== null ? (int) $data['customer_id'] : null,
            (int) $data['user_id'],
            (string) $data['total'],
            (string) $data['total_paid'],
            (string) $data['change_amount'],
            (string) $data['status'],
            (string) $data['created_at']
        );
    }
}

