<?php

namespace App\Services;

use App\Entities\Customer;
use App\Repositories\CustomerRepository;
use PDOException;

final class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function list(): array
    {
        $customers = $this->customerRepository->list();

        return array_map(fn (Customer $customer) => $this->serialize($customer), $customers);
    }

    public function findById(int $id): array
    {
        $customer = $this->customerRepository->findById($id);

        if (!$customer) {
            throw new \RuntimeException('Customer not found');
        }

        return $this->serialize($customer);
    }

    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $email === '' || $phone === '') {
            throw new \InvalidArgumentException('name, email and phone are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid email format');
        }

        $customer = new Customer(null, $name, $email, $phone);

        try {
            $created = $this->customerRepository->create($customer);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                throw new \DomainException('Email already exists');
            }

            throw $e;
        }

        return $this->serialize($created);
    }

    public function update(int $id, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $email === '' || $phone === '') {
            throw new \InvalidArgumentException('name, email and phone are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid email format');
        }

        $customer = new Customer($id, $name, $email, $phone);

        try {
            $updated = $this->customerRepository->update($customer);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                throw new \DomainException('Email already exists');
            }

            throw $e;
        }

        return $this->serialize($updated);
    }

    public function delete(int $id): void
    {
        $customer = $this->customerRepository->findById($id);

        if (!$customer) {
            throw new \RuntimeException('Customer not found');
        }

        $this->customerRepository->delete($id);
    }

    private function serialize(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ];
    }
}
