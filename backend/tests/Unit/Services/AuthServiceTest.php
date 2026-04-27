<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Entities\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
	private string $jwtSecret;

	protected function setUp(): void
	{
		$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
		$dotenv->safeLoad();
		$this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'test-secret-key-with-at-least-32-bytes';
	}

	private function createPdo(): PDO
	{
		$pdo = new PDO('sqlite::memory:');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL)');

		return $pdo;
	}

	public function test_register_creates_user_and_returns_token(): void
	{
		$pdo = $this->createPdo();
		$repo = new UserRepository($pdo);
		$service = new AuthService($pdo, $repo, $this->jwtSecret);

		$result = $service->register([
			'name' => 'Ana',
			'email' => 'ana@example.com',
			'password' => 'secret123',
			'confirm_password' => 'secret123',
		]);

		self::assertNotSame('', $result['token']);
		self::assertSame('Ana', $result['user']['name']);
		self::assertSame('ana@example.com', $result['user']['email']);

		$created = $repo->findByEmail('ana@example.com');
		self::assertInstanceOf(User::class, $created);
		self::assertTrue(password_verify('secret123', $created->password));
	}

	public function test_register_throws_when_email_already_exists(): void
	{
		$pdo = $this->createPdo();
		$repo = new UserRepository($pdo);
		$repo->create(new User(null, 'Ana', 'ana@example.com', password_hash('secret123', PASSWORD_DEFAULT)));

		$service = new AuthService($pdo, $repo, $this->jwtSecret);

		$this->expectException(\DomainException::class);
		$this->expectExceptionMessage('Email already exists');

		$service->register([
			'name' => 'Ana',
			'email' => 'ana@example.com',
			'password' => 'secret123',
			'confirm_password' => 'secret123',
		]);
	}

	public function test_login_throws_for_invalid_credentials(): void
	{
		$pdo = $this->createPdo();
		$repo = new UserRepository($pdo);
		$repo->create(new User(null, 'Ana', 'ana@example.com', password_hash('other-password', PASSWORD_DEFAULT)));

		$service = new AuthService($pdo, $repo, $this->jwtSecret);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid credentials');

		$service->login([
			'email' => 'ana@example.com',
			'password' => 'wrong',
		]);
	}

	public function test_validate_token_returns_user(): void
	{
		$pdo = $this->createPdo();
		$repo = new UserRepository($pdo);
		$service = new AuthService($pdo, $repo, $this->jwtSecret);

		$registered = $service->register([
			'name' => 'Ana',
			'email' => 'ana@example.com',
			'password' => 'secret123',
			'confirm_password' => 'secret123',
		]);

		$user = $service->validateToken($registered['token']);

		self::assertSame($registered['user']['id'], $user->id);
		self::assertSame('Ana', $user->name);
	}
}
