<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PaymentMethodType;
use App\Repositories\PaymentMethodRepository;
use App\Services\PaymentMethodService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PaymentMethodServiceTest extends TestCase
{
	private function createService(): PaymentMethodService
	{
		$pdo = new PDO('sqlite::memory:');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$pdo->exec('CREATE TABLE payment_methods (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, type TEXT NOT NULL, status INTEGER NOT NULL)');

		return new PaymentMethodService(new PaymentMethodRepository($pdo));
	}

	public function test_create_accepts_valid_type(): void
	{
		$service = $this->createService();

		$result = $service->create([
			'name' => 'Dinheiro',
			'type' => 'cash',
			'status' => true,
		]);

		self::assertIsInt($result['id']);
		self::assertSame(PaymentMethodType::CASH->value, $result['type']);
	}

	public function test_create_throws_for_invalid_type(): void
	{
		$service = $this->createService();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('type must be ELECTRONIC or CASH');

		$service->create([
			'name' => 'Cheque',
			'type' => 'CHECK',
		]);
	}

	public function test_delete_throws_when_not_found(): void
	{
		$service = $this->createService();

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Payment method not found');

		$service->delete(999);
	}
}
