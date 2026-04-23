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

    public function register(array $data): void
    {
        $existing = $this->userRepository->findByEmail($data['email']);

        if ($existing) {
            throw new \Exception('Email already exists');
        }

        $this->userRepository->create(
            new User(
                null,
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            )
        );
    }

    public function login(array $data): string
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + 3600
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function validateToken(string $token): object
    {
        return JWT::decode(
            $token,
            new Key($this->jwtSecret, 'HS256')
        );
    }
}