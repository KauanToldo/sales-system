<?php

namespace App\Repositories;

use PDO;
use App\Entities\User;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) return null;

        return new User(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['password']
        );
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $data = $stmt->fetch();

        if (!$data) return null;

        return new User(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['password']
        );
    }

    public function create(User $user): User
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password)
            VALUES (?, ?, ?)
            RETURNING id, name, email, password
        ");

        $stmt->execute([
            $user->name,
            $user->email,
            $user->password
        ]);

        $data = $stmt->fetch();

        return new User(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['password']
        );
    }
}