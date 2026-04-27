<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\PaymentMethodRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleItemRepository;
use App\Repositories\SaleRepository;
use App\Repositories\UserRepository;
use App\Services\SaleCalculator;
use App\Services\SaleService;
use PDO;
use PHPUnit\Framework\TestCase;

final class SaleServiceTest extends TestCase
{
	private function createPdo(): PDO
	{
		$pdo = new PDO('sqlite::memory:');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

		$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL)');
		$pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, sku TEXT NOT NULL UNIQUE, name TEXT NOT NULL, category TEXT NOT NULL, price TEXT NOT NULL)');
		$pdo->exec('CREATE TABLE payment_methods (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, type TEXT NOT NULL, status INTEGER NOT NULL)');
		$pdo->exec('CREATE TABLE sales (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER NULL, user_id INTEGER NOT NULL, total TEXT NOT NULL, total_paid TEXT NOT NULL, change_amount TEXT NOT NULL, status TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
		$pdo->exec('CREATE TABLE sale_items (id INTEGER PRIMARY KEY AUTOINCREMENT, sale_id INTEGER NOT NULL, product_id INTEGER NOT NULL, quantity INTEGER NOT NULL, unit_price TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
		$pdo->exec('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, sale_id INTEGER NOT NULL, payment_method_id INTEGER NOT NULL, amount TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');

		return $pdo;
	}

	private function createService(PDO $pdo): SaleService
	{
		return new SaleService(
			$pdo,
			new SaleRepository($pdo),
			new SaleItemRepository($pdo),
			new PaymentRepository($pdo),
			new ProductRepository($pdo),
			new PaymentMethodRepository($pdo),
			new UserRepository($pdo),
			new SaleCalculator()
		);
	}

	public function test_create_open_sale_with_items_and_payments(): void
	{
		$pdo = $this->createPdo();
		$service = $this->createService($pdo);

		$pdo->exec("INSERT INTO users (id, name, email, password) VALUES (1, 'Admin', 'admin@example.com', 'hash')");
		$pdo->exec("INSERT INTO products (id, sku, name, category, price) VALUES (10, 'SKU-10', 'Teclado', 'Perifericos', '100.00')");
		$pdo->exec("INSERT INTO payment_methods (id, name, type, status) VALUES (20, 'Dinheiro', 'CASH', 1)");

		$result = $service->createOpen([
			'customer_id' => 3,
			'items' => [
				['product_id' => 10, 'quantity' => 1],
			],
			'payments' => [
				['payment_method_id' => 20, 'amount' => '120.00'],
			],
		], 1);

		self::assertSame('100.00', $result['total']);
		self::assertSame('120.00', $result['total_paid']);
		self::assertSame('20.00', $result['change_amount']);
		self::assertCount(1, $result['items']);
		self::assertCount(1, $result['payments']);
	}

	public function test_add_payment_rejects_non_open_sale(): void
	{
		$pdo = $this->createPdo();
		$service = $this->createService($pdo);

		$pdo->exec("INSERT INTO sales (id, customer_id, user_id, total, total_paid, change_amount, status, created_at) VALUES (5, NULL, 1, '10.00', '10.00', '0.00', 'FINALIZED', '2026-04-26 10:00:00')");

		$this->expectException(\DomainException::class);
		$this->expectExceptionMessage('only OPEN sales can receive payments');

		$service->addPayment(5, ['payment_method_id' => 1, 'amount' => '5.00']);
	}

	public function test_finalize_throws_when_payment_is_insufficient(): void
	{
		$pdo = $this->createPdo();
		$service = $this->createService($pdo);

		$pdo->exec("INSERT INTO payment_methods (id, name, type, status) VALUES (20, 'Cartao', 'ELECTRONIC', 1)");
		$pdo->exec("INSERT INTO sales (id, customer_id, user_id, total, total_paid, change_amount, status, created_at) VALUES (7, NULL, 1, '0.00', '0.00', '0.00', 'OPEN', '2026-04-26 10:00:00')");
		$pdo->exec("INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, created_at) VALUES (1, 7, 10, 1, '50.00', '2026-04-26 10:00:01')");
		$pdo->exec("INSERT INTO payments (id, sale_id, payment_method_id, amount, created_at) VALUES (1, 7, 20, '20.00', '2026-04-26 10:00:02')");

		$this->expectException(\DomainException::class);
		$this->expectExceptionMessage('total paid must be greater than or equal to total');

		$service->finalize(7);
	}
}
