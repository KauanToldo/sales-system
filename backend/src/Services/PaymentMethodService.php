<?php

namespace App\Services;

use App\Entities\PaymentMethod;
use App\Enums\PaymentMethodType;
use App\Repositories\PaymentMethodRepository;
use PDOException;

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
        $type = strtoupper(trim((string) ($data['type'] ?? '')));
        $status = isset($data['status']) ? (bool) $data['status'] : true;

        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('name and type are required');
        }

        if (!in_array($type, [PaymentMethodType::ELECTRONIC->value, PaymentMethodType::CASH->value], true)) {
            throw new \InvalidArgumentException('type must be ELECTRONIC or CASH');
        }

        $method = new PaymentMethod(null, $name, $type, $status);
        $created = $this->paymentMethodRepository->create($method);

        return $this->serialize($created);
    }

    public function update(int $id, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = strtoupper(trim((string) ($data['type'] ?? '')));

        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('name and type are required');
        }

        if (!in_array($type, [PaymentMethodType::ELECTRONIC->value, PaymentMethodType::CASH->value], true)) {
            throw new \InvalidArgumentException('type must be ELECTRONIC or CASH');
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

        try {
            $this->paymentMethodRepository->delete($id);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new \DomainException('This payment method cannot be deleted because it is already used in one or more sales. You can deactivate it instead.');
            }

            throw $e;
        }
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
