<?php

namespace App\Services;

use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repositories\UserRepository;
use App\Entities\User;

class AuthService
{
    public function __construct(
        private PDO $db,
        private UserRepository $userRepository,
        private string $jwtSecret
    ) {}

    public function register(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            throw new \InvalidArgumentException('Name, email, password and confirm password are required');
        }

        if(strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }

        if ($password !== $confirmPassword) {
            throw new \InvalidArgumentException('Passwords do not match');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        $existing = $this->userRepository->findByEmail($email);

        if ($existing) {
            throw new \DomainException('Email already exists');
        }

        $user = $this->userRepository->create(
            new User(
                null,
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            )
        );

        return [
            'token' => $this->buildToken($user),
            'user' => $this->serializeUser($user),
        ];
    }

    public function login(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new \InvalidArgumentException('Email and password are required');
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            throw new \RuntimeException('Invalid credentials');
        }

        return [
            'token' => $this->buildToken($user),
            'user' => $this->serializeUser($user),
        ];
    }

    public function validateToken(string $token): User
    {
        $decoded = JWT::decode(
            $token,
            new Key($this->jwtSecret, 'HS256')
        );

        $userId = isset($decoded->sub) ? (int) $decoded->sub : 0;

        if ($userId <= 0) {
            throw new \RuntimeException('Unauthorized');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \RuntimeException('Unauthorized');
        }

        return $user;
    }

    private function buildToken(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}