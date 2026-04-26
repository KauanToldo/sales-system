<?php

namespace App\Repositories;

use PDO;
use App\Entities\Customer;

final class CustomerRepository
{
    public function __construct(private PDO $pdo) {}
 
    public function list(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM customers");
        $customers = [];

        while ($data = $stmt->fetch()) {
            $customers[] = new Customer(
                $data['id'],
                $data['name'],
                $data['email'],
                $data['phone']
            );
        }

        return $customers;
    }

    public function findById(int $id): ?Customer
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        $data = $stmt->fetch();

        if (!$data) return null;

        return new Customer(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['phone']
        );
    }

    public function create(Customer $customer): Customer
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO customers (name, email, phone)
            VALUES (?, ?, ?)
            RETURNING id, name, email, phone
        ");

        $stmt->execute([
            $customer->name,
            $customer->email,
            $customer->phone
        ]);

        $data = $stmt->fetch();

        return new Customer(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['phone']
        );
    }

    public function update(Customer $customer): Customer
    {
        $stmt = $this->pdo->prepare("
            UPDATE customers
            SET name = ?, email = ?, phone = ?
            WHERE id = ?
            RETURNING id, name, email, phone
        ");

        $stmt->execute([
            $customer->name,
            $customer->email,
            $customer->phone,
            $customer->id
        ]);

        $data = $stmt->fetch();

        if (!$data) {
            throw new \RuntimeException('Customer not found');
        }

        return new Customer(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['phone']
        );
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
    }

}