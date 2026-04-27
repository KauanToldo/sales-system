<?php

namespace App\Repositories;

use PDO;
use App\Entities\Product;

final class ProductRepository
{
    public function __construct(private PDO $pdo) {}

    public function list(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM products");
        $products = [];

        while ($data = $stmt->fetch()) {
            $products[] = new Product(
                $data['id'],
                $data['sku'],
                $data['name'],
                $data['category'],
                (string)$data['price']
            );
        }

        return $products;
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) return null;

        return new Product(
            $data['id'],
            $data['sku'],
            $data['name'],
            $data['category'],
            (string)$data['price']
        );
    }

    public function existsBySkuIgnoreCase(string $sku, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM products WHERE LOWER(sku) = LOWER(?) AND id <> ? LIMIT 1");
            $stmt->execute([$sku, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT 1 FROM products WHERE LOWER(sku) = LOWER(?) LIMIT 1");
            $stmt->execute([$sku]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public function create(Product $product): Product
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (sku, name, category, price)
            VALUES (?, ?, ?, ?)
            RETURNING id, sku, name, category, price
        ");

        $stmt->execute([
            $product->sku,
            $product->name,
            $product->category,
            $product->price
        ]);

        $data = $stmt->fetch();

        return new Product(
            $data['id'],
            $data['sku'],
            $data['name'],
            $data['category'],
            (string)$data['price']
        );
    }

    public function update(Product $product): Product
    {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET sku = ?, name = ?, category = ?, price = ?
            WHERE id = ?
            RETURNING id, sku, name, category, price
        ");

        $stmt->execute([
            $product->sku,
            $product->name,
            $product->category,
            $product->price,
            $product->id
        ]);

        $data = $stmt->fetch();

        if (!$data) {
            throw new \RuntimeException('Product not found');
        }

        return new Product(
            $data['id'],
            $data['sku'],
            $data['name'],
            $data['category'],
            (string)$data['price']
        );
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }
}