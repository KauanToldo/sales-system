<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\ProductRepository;
use App\Services\ProductService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
	private function createService(): ProductService
	{
		$pdo = new PDO('sqlite::memory:');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, sku TEXT NOT NULL UNIQUE, name TEXT NOT NULL, category TEXT NOT NULL, price TEXT NOT NULL)');

		return new ProductService(new ProductRepository($pdo));
	}

	public function test_create_normalizes_sku_and_price(): void
	{
		$service = $this->createService();

		$result = $service->create([
			'sku' => 'abc-001',
			'name' => 'Notebook',
			'category' => 'Eletronicos',
			'price' => '12.5',
		]);

		self::assertSame('ABC-001', $result['sku']);
		self::assertSame('12.50', $result['price']);
	}

	public function test_create_throws_when_sku_already_exists(): void
	{
		$service = $this->createService();

		$service->create([
			'sku' => 'ABC',
			'name' => 'Produto Base',
			'category' => 'Cat',
			'price' => '10.00',
		]);

		$this->expectException(\DomainException::class);
		$this->expectExceptionMessage('SKU already exists');

		$service->create([
			'sku' => 'abc',
			'name' => 'Produto 2',
			'category' => 'Cat',
			'price' => '10.00',
		]);
	}

	public function test_create_throws_for_invalid_price_format(): void
	{
		$service = $this->createService();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('price must be a decimal value with up to 2 decimal places');

		$service->create([
			'sku' => 'ABC',
			'name' => 'Produto',
			'category' => 'Cat',
			'price' => '10,00',
		]);
	}
}
