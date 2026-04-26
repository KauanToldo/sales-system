<?php

namespace App\Repositories;

use App\Entities\SaleItem;
use PDO;

final class SaleItemRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(int $saleId, int $productId, int $quantity, string $unitPrice): SaleItem
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sale_items (sale_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
            RETURNING id, sale_id, product_id, quantity, unit_price, created_at
        ");

        $stmt->execute([$saleId, $productId, $quantity, $unitPrice]);
        $data = $stmt->fetch();

        return new SaleItem(
            (int) $data['id'],
            (int) $data['sale_id'],
            (int) $data['product_id'],
            (int) $data['quantity'],
            (string) $data['unit_price'],
            (string) $data['created_at']
        );
    }

    public function listBySaleId(int $saleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, sale_id, product_id, quantity, unit_price, created_at
            FROM sale_items
            WHERE sale_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$saleId]);

        $items = [];
        while ($data = $stmt->fetch()) {
            $items[] = new SaleItem(
                (int) $data['id'],
                (int) $data['sale_id'],
                (int) $data['product_id'],
                (int) $data['quantity'],
                (string) $data['unit_price'],
                (string) $data['created_at']
            );
        }

        return $items;
    }
}

