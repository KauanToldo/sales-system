<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SalePdfService;
use PDO;
use PHPUnit\Framework\TestCase;

final class SalePdfServiceTest extends TestCase
{
    public function test_generate_throws_when_sale_not_found(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $this->createSchema($pdo);

        $service = new SalePdfService($pdo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sale not found');

        $service->generate(999);
    }

    public function test_generate_throws_when_sale_is_not_finalized(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $this->createSchema($pdo);
        $this->seedBaseData($pdo);

        $pdo->exec("INSERT INTO sales (id, customer_id, user_id, total, total_paid, change_amount, status, created_at) VALUES (1, 1, 1, '10.00', '10.00', '0.00', 'OPEN', '2026-04-26 11:00:00')");

        $service = new SalePdfService($pdo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('sale must be FINALIZED to generate pdf');

        $service->generate(1);
    }

    public function test_generate_returns_pdf_binary_for_finalized_sale(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $this->createSchema($pdo);
        $this->seedBaseData($pdo);

        $pdo->exec("INSERT INTO sales (id, customer_id, user_id, total, total_paid, change_amount, status, created_at) VALUES (2, 1, 1, '30.00', '30.00', '0.00', 'FINALIZED', '2026-04-26 11:00:00')");
        $pdo->exec("INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price) VALUES (1, 2, 1, 2, '15.00')");
        $pdo->exec("INSERT INTO payments (id, sale_id, payment_method_id, amount) VALUES (1, 2, 1, '30.00')");

        $service = new SalePdfService($pdo);

        $pdf = $service->generate(2);

        self::assertNotSame('', $pdf);
        self::assertStringStartsWith('%PDF', $pdf);
    }

    private function createSchema(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT)');
        $pdo->exec('CREATE TABLE customers (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT)');
        $pdo->exec('CREATE TABLE payment_methods (id INTEGER PRIMARY KEY, name TEXT, type TEXT, status INTEGER)');
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, sku TEXT, name TEXT, category TEXT, price TEXT)');
        $pdo->exec('CREATE TABLE sales (id INTEGER PRIMARY KEY, customer_id INTEGER, user_id INTEGER NOT NULL, total TEXT, total_paid TEXT, change_amount TEXT, status TEXT, created_at TEXT)');
        $pdo->exec('CREATE TABLE sale_items (id INTEGER PRIMARY KEY, sale_id INTEGER NOT NULL, product_id INTEGER NOT NULL, quantity INTEGER, unit_price TEXT)');
        $pdo->exec('CREATE TABLE payments (id INTEGER PRIMARY KEY, sale_id INTEGER NOT NULL, payment_method_id INTEGER NOT NULL, amount TEXT)');
    }

    private function seedBaseData(PDO $pdo): void
    {
        $pdo->exec("INSERT INTO users (id, name, email, password) VALUES (1, 'Admin', 'admin@example.com', 'hash')");
        $pdo->exec("INSERT INTO customers (id, name, email, phone) VALUES (1, 'Cliente', 'cliente@example.com', '11999999999')");
        $pdo->exec("INSERT INTO payment_methods (id, name, type, status) VALUES (1, 'Dinheiro', 'CASH', 1)");
        $pdo->exec("INSERT INTO products (id, sku, name, category, price) VALUES (1, 'SKU1', 'Produto', 'Cat', '15.00')");
    }
}
