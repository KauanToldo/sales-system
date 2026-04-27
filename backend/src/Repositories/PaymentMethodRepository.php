<?php

namespace App\Repositories;

use PDO;
use App\Entities\PaymentMethod;

final class PaymentMethodRepository
{
    public function __construct(private PDO $pdo) {}

    public function list(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_methods");
        $methods = [];

        while ($data = $stmt->fetch()) {
            $methods[] = new PaymentMethod(
                $data['id'],
                $data['name'],
                $data['type'],
                (bool)$data['status']
            );
        }

        return $methods;
    }

    public function findById(int $id): ?PaymentMethod
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) return null;

        return new PaymentMethod(
            $data['id'],
            $data['name'],
            $data['type'],
            (bool)$data['status']
        );
    }

    public function create(PaymentMethod $method): PaymentMethod
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_methods (name, type, status)
            VALUES (?, ?, ?)
            RETURNING id, name, type, status
        ");

        $stmt->execute([
            $method->name,
            $method->type,
            (int)$method->status
        ]);

        $data = $stmt->fetch();

        return new PaymentMethod(
            $data['id'],
            $data['name'],
            $data['type'],
            (bool)$data['status']
        );
    }

    public function update(PaymentMethod $method): PaymentMethod
    {
        $stmt = $this->pdo->prepare("
            UPDATE payment_methods
            SET name = ?, type = ?, status = ?
            WHERE id = ?
            RETURNING id, name, type, status
        ");

        $stmt->execute([
            $method->name,
            $method->type,
            (int)$method->status,
            $method->id
        ]);

        $data = $stmt->fetch();

        if (!$data) {
            throw new \RuntimeException('Payment method not found');
        }

        return new PaymentMethod(
            $data['id'],
            $data['name'],
            $data['type'],
            (bool)$data['status']
        );
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);
    }
}