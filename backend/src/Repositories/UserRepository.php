<?php

namespace App\Repositories;

use PDO;
use App\Entities\User;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

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

    public function create(User $user): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $user->name,
            $user->email,
            $user->password
        ]);
    }
}