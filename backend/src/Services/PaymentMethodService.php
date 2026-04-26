<?php

namespace App\Services;

use App\Entities\PaymentMethod;
use App\Repositories\PaymentMethodRepository;

final class PaymentMethodService
{
    public function __construct(private PaymentMethodRepository $paymentMethodRepository) {}

    public function list(): array
    {
        $methods = $this->paymentMethodRepository->list();

        return array_map(fn (PaymentMethod $method) => $this->serialize($method), $methods);
    }

    public function findById(int $id): array
    {
        $method = $this->paymentMethodRepository->findById($id);

        if (!$method) {
            throw new \RuntimeException('Payment method not found');
        }

        return $this->serialize($method);
    }

    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? ''));
        $status = isset($data['status']) ? (bool) $data['status'] : true;

        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('name and type are required');
        }

        $method = new PaymentMethod(null, $name, $type, $status);
        $created = $this->paymentMethodRepository->create($method);

        return $this->serialize($created);
    }

    public function update(int $id, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? ''));

        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('name and type are required');
        }

        if (!array_key_exists('status', $data)) {
            throw new \InvalidArgumentException('status is required');
        }

        $status = (bool) $data['status'];

        $method = new PaymentMethod($id, $name, $type, $status);
        $updated = $this->paymentMethodRepository->update($method);

        return $this->serialize($updated);
    }

    public function delete(int $id): void
    {
        $method = $this->paymentMethodRepository->findById($id);

        if (!$method) {
            throw new \RuntimeException('Payment method not found');
        }

        $this->paymentMethodRepository->delete($id);
    }

    private function serialize(PaymentMethod $method): array
    {
        return [
            'id' => $method->id,
            'name' => $method->name,
            'type' => $method->type,
            'status' => $method->status,
        ];
    }
}
