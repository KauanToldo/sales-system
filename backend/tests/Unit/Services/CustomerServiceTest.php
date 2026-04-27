<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\CustomerRepository;
use App\Services\CustomerService;
use PDO;
use PHPUnit\Framework\TestCase;

final class CustomerServiceTest extends TestCase
{
	private function createService(): CustomerService
	{
		$pdo = new PDO('sqlite::memory:');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$pdo->exec('CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, phone TEXT NOT NULL)');

		return new CustomerService(new CustomerRepository($pdo));
	}

	public function test_create_returns_serialized_customer(): void
	{
		$service = $this->createService();

		$result = $service->create([
			'name' => 'Joao',
			'email' => 'joao@example.com',
			'phone' => '11999999999',
		]);

		self::assertIsInt($result['id']);
		self::assertSame('Joao', $result['name']);
		self::assertSame('joao@example.com', $result['email']);
	}

	public function test_create_throws_for_invalid_email(): void
	{
		$service = $this->createService();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid email format');

		$service->create([
			'name' => 'Joao',
			'email' => 'invalido',
			'phone' => '11999999999',
		]);
	}

	public function test_delete_throws_when_customer_not_found(): void
	{
		$service = $this->createService();

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Customer not found');

		$service->delete(123);
	}
}
