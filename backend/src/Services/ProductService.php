<?php

namespace App\Services;

use App\Entities\Product;
use App\Repositories\ProductRepository;
use PDOException;

final class ProductService
{
    public function __construct(private ProductRepository $productRepository) {}

    public function list(): array
    {
        $products = $this->productRepository->list();

        return array_map(fn (Product $product) => $this->serialize($product), $products);
    }

    public function findById(int $id): array
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        return $this->serialize($product);
    }

    public function create(array $data): array
    {
        $sku = strtoupper(trim((string) ($data['sku'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $price = $this->normalizeMoney((string) ($data['price'] ?? ''));

        if ($sku === '' || $name === '' || $category === '') {
            throw new \InvalidArgumentException('sku, name and category are required');
        }

        if ($this->productRepository->existsBySkuIgnoreCase($sku)) {
            throw new \DomainException('SKU already exists');
        }

        $product = new Product(null, $sku, $name, $category, $price);

        try {
            $created = $this->productRepository->create($product);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                throw new \DomainException('SKU already exists');
            }

            throw $e;
        }

        return $this->serialize($created);
    }

    public function update(int $id, array $data): array
    {
        $sku = strtoupper(trim((string) ($data['sku'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $price = $this->normalizeMoney((string) ($data['price'] ?? ''));

        if ($sku === '' || $name === '' || $category === '') {
            throw new \InvalidArgumentException('sku, name and category are required');
        }

        if ($this->productRepository->existsBySkuIgnoreCase($sku, $id)) {
            throw new \DomainException('SKU already exists');
        }

        $product = new Product($id, $sku, $name, $category, $price);

        try {
            $updated = $this->productRepository->update($product);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                throw new \DomainException('SKU already exists');
            }

            throw $e;
        }

        return $this->serialize($updated);
    }

    public function delete(int $id): void
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        $this->productRepository->delete($id);
    }

    private function normalizeMoney(string $value): string
    {
        $normalized = trim($value);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('price must be a decimal value with up to 2 decimal places');
        }

        if (!str_contains($normalized, '.')) {
            $normalized .= '.00';
        }

        [$integer, $decimal] = explode('.', $normalized, 2);
        $decimal = str_pad($decimal, 2, '0');
        $cents = ((int) $integer * 100) + (int) $decimal;

        if ($cents <= 0) {
            throw new \InvalidArgumentException('price must be greater than zero');
        }

        return $integer . '.' . $decimal;
    }

    private function serialize(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category,
            'price' => $product->price,
        ];
    }
}
