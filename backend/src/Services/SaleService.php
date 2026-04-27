<?php

namespace App\Services;

use App\Entities\Sale;
use App\Entities\SaleItem;
use App\Entities\Payment;
use App\Enums\PaymentMethodType;
use App\Enums\SaleStatus;
use App\Repositories\SaleRepository;
use App\Repositories\SaleItemRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\UserRepository;
use PDO;

final class SaleService
{
    public function __construct(
        private PDO $pdo,
        private SaleRepository $saleRepository,
        private SaleItemRepository $saleItemRepository,
        private PaymentRepository $paymentRepository,
        private ProductRepository $productRepository,
        private PaymentMethodRepository $paymentMethodRepository,
        private UserRepository $userRepository,
        private SaleCalculator $saleCalculator
    ) {}

    public function list(int $limit = 50, int $offset = 0): array
    {
        $sales = $this->saleRepository->list($limit, $offset);

        return array_map(fn (Sale $sale) => $this->serialize($sale, false), $sales);
    }

    public function findById(int $id): array
    {
        $sale = $this->saleRepository->findById($id);
        if (!$sale) {
            throw new \RuntimeException('Sale not found');
        }

        $sale->items = $this->saleItemRepository->listBySaleId($sale->id ?? 0);
        $sale->payments = $this->paymentRepository->listBySaleId($sale->id ?? 0);

        return $this->serialize($sale, true);
    }

    /**
     * Creates an OPEN sale. Payments are optional.
     * @param array $data { customer_id?:int|null, items: [{product_id:int, quantity:int}], payments?: [{payment_method_id:int, amount:string}] }
     */
    public function createOpen(array $data, int $userId): array
    {
        if ($userId <= 0) {
            throw new \RuntimeException('invalid authenticated user');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \RuntimeException('authenticated user not found');
        }

        $customerId = isset($data['customer_id']) && $data['customer_id'] !== '' ? (int) $data['customer_id'] : null;
        $items = $data['items'] ?? null;
        $payments = $data['payments'] ?? [];

        if (!is_array($items) || count($items) < 1) {
            throw new \InvalidArgumentException('items must be a non-empty array');
        }
        if (!is_array($payments)) {
            throw new \InvalidArgumentException('payments must be an array');
        }

        $preparedItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('each item must be an object');
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new \InvalidArgumentException('item product_id and quantity are required');
            }

            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new \InvalidArgumentException("product_id {$productId} not found");
            }

            $preparedItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ];
        }

        $preparedPayments = [];
        foreach ($payments as $payment) {
            if (!is_array($payment)) {
                throw new \InvalidArgumentException('each payment must be an object');
            }

            $paymentMethodId = (int) ($payment['payment_method_id'] ?? 0);
            $amount = trim((string) ($payment['amount'] ?? ''));

            if ($paymentMethodId <= 0 || $amount === '') {
                throw new \InvalidArgumentException('payment_method_id and amount are required');
            }

            $method = $this->paymentMethodRepository->findById($paymentMethodId);
            if (!$method) {
                throw new \InvalidArgumentException("payment_method_id {$paymentMethodId} not found");
            }

            if (!$method->status) {
                throw new \DomainException('payment method is disabled');
            }

            $type = strtoupper(trim($method->type));

            if (!in_array($type, [PaymentMethodType::ELECTRONIC->value, PaymentMethodType::CASH->value], true)) {
                throw new \DomainException('invalid payment method type');
            }

            $isCash = $type === PaymentMethodType::CASH->value;

            $preparedPayments[] = [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'is_cash' => $isCash,
            ];
        }

        $calc = $this->saleCalculator->calculate([
            'items' => array_map(fn ($i) => ['quantity' => $i['quantity'], 'unit_price' => $i['unit_price']], $preparedItems),
            'payments' => array_map(fn ($p) => ['amount' => $p['amount'], 'is_cash' => $p['is_cash']], $preparedPayments),
        ], false);

        try {
            $this->pdo->beginTransaction();

            $sale = $this->saleRepository->create(
                $customerId,
                $userId,
                $calc['total'],
                $calc['total_paid'],
                $calc['change_amount'],
                SaleStatus::OPEN->value
            );

            $saleItems = [];
            foreach ($preparedItems as $item) {
                $saleItems[] = $this->saleItemRepository->create(
                    $sale->id ?? 0,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price']
                );
            }

            $salePayments = [];
            foreach ($preparedPayments as $payment) {
                $salePayments[] = $this->paymentRepository->create(
                    $sale->id ?? 0,
                    $payment['payment_method_id'],
                    $payment['amount']
                );
            }

            $this->pdo->commit();

            $sale->items = $saleItems;
            $sale->payments = $salePayments;

            return $this->serialize($sale, true);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Adds a payment to an OPEN sale and recalculates totals.
     */
    public function addPayment(int $saleId, array $data): array
    {
        $sale = $this->saleRepository->findById($saleId);
        if (!$sale) {
            throw new \RuntimeException('Sale not found');
        }
        if ($sale->status !== SaleStatus::OPEN->value) {
            throw new \DomainException('only OPEN sales can receive payments');
        }

        $paymentMethodId = (int) ($data['payment_method_id'] ?? 0);
        $amount = trim((string) ($data['amount'] ?? ''));

        if ($paymentMethodId <= 0 || $amount === '') {
            throw new \InvalidArgumentException('payment_method_id and amount are required');
        }

        $method = $this->paymentMethodRepository->findById($paymentMethodId);
        if (!$method) {
            throw new \InvalidArgumentException("payment_method_id {$paymentMethodId} not found");
        }
        if (!$method->status) {
            throw new \DomainException('payment method is disabled');
        }

        $type = strtoupper(trim($method->type));
        if (!in_array($type, [PaymentMethodType::ELECTRONIC->value, PaymentMethodType::CASH->value], true)) {
            throw new \DomainException('invalid payment method type');
        }
        $isCash = $type === PaymentMethodType::CASH->value;

        $items = $this->saleItemRepository->listBySaleId($saleId);
        $payments = $this->paymentRepository->listBySaleId($saleId);

        $preparedPayments = [];
        foreach ($payments as $payment) {
            $existingMethod = $this->paymentMethodRepository->findById($payment->paymentMethodId);
            if (!$existingMethod) {
                throw new \DomainException('invalid payment method on sale');
            }

            $existingType = strtoupper(trim($existingMethod->type));
            if (!in_array($existingType, [PaymentMethodType::ELECTRONIC->value, PaymentMethodType::CASH->value], true)) {
                throw new \DomainException('invalid payment method type');
            }

            $preparedPayments[] = [
                'amount' => $payment->amount,
                'is_cash' => $existingType === PaymentMethodType::CASH->value,
            ];
        }

        // Add the new one
        $preparedPayments[] = ['amount' => $amount, 'is_cash' => $isCash];

        $calc = $this->saleCalculator->calculate([
            'items' => array_map(fn (SaleItem $i) => ['quantity' => $i->quantity, 'unit_price' => $i->unitPrice], $items),
            'payments' => $preparedPayments,
        ], false);

        try {
            $this->pdo->beginTransaction();

            $payment = $this->paymentRepository->create($saleId, $paymentMethodId, $amount);
            $this->saleRepository->updateTotals($saleId, $calc['total'], $calc['total_paid'], $calc['change_amount']);

            $this->pdo->commit();

            $sale = $this->saleRepository->findById($saleId) ?? throw new \RuntimeException('Sale not found');
            $sale->items = $items;
            $sale->payments = array_merge($payments, [$payment]);

            return $this->serialize($sale, true);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Finalizes an OPEN sale (requires paid in full, applies change rules).
     */
    public function finalize(int $saleId): array
    {
        $sale = $this->saleRepository->findById($saleId);
        if (!$sale) {
            throw new \RuntimeException('Sale not found');
        }
        if ($sale->status !== SaleStatus::OPEN->value) {
            throw new \DomainException('only OPEN sales can be finalized');
        }

        $items = $this->saleItemRepository->listBySaleId($saleId);
        $payments = $this->paymentRepository->listBySaleId($saleId);

        // Map payments -> amount + is_cash (lookup type from payment_methods)
        $preparedPayments = [];
        foreach ($payments as $payment) {
            $method = $this->paymentMethodRepository->findById($payment->paymentMethodId);
            if (!$method) {
                throw new \DomainException('invalid payment method on sale');
            }
            $type = strtoupper(trim($method->type));
            $preparedPayments[] = [
                'amount' => $payment->amount,
                'is_cash' => $type === PaymentMethodType::CASH->value,
            ];
        }

        $calc = $this->saleCalculator->calculate([
            'items' => array_map(fn (SaleItem $i) => ['quantity' => $i->quantity, 'unit_price' => $i->unitPrice], $items),
            'payments' => $preparedPayments,
        ], true);

        try {
            $this->pdo->beginTransaction();

            $this->saleRepository->updateTotals($saleId, $calc['total'], $calc['total_paid'], $calc['change_amount']);
            $this->saleRepository->updateStatus($saleId, SaleStatus::FINALIZED->value);

            $this->pdo->commit();

            $sale = $this->saleRepository->findById($saleId) ?? throw new \RuntimeException('Sale not found');
            $sale->items = $items;
            $sale->payments = $payments;

            return $this->serialize($sale, true);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function serialize(Sale $sale, bool $includeChildren): array
    {
        $payload = [
            'id' => $sale->id,
            'customer_id' => $sale->customerId,
            'user_id' => $sale->userId,
            'total' => $sale->total,
            'total_paid' => $sale->totalPaid,
            'change_amount' => $sale->changeAmount,
            'status' => $sale->status,
            'created_at' => $sale->createdAt,
        ];

        if ($includeChildren) {
            $payload['items'] = array_map(fn (SaleItem $item) => [
                'id' => $item->id,
                'sale_id' => $item->saleId,
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
                'created_at' => $item->createdAt,
            ], $sale->items);

            $payload['payments'] = array_map(fn (Payment $payment) => [
                'id' => $payment->id,
                'sale_id' => $payment->saleId,
                'payment_method_id' => $payment->paymentMethodId,
                'amount' => $payment->amount,
                'created_at' => $payment->createdAt,
            ], $sale->payments);
        }

        return $payload;
    }
}

