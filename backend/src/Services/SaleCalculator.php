<?php

namespace App\Services;

final class SaleCalculator
{
    /**
     * @param array{items: array<int, array{quantity:mixed, unit_price:mixed}>, payments: array<int, array{amount:mixed, is_cash:bool}>} $input
     * @return array{total:string, total_paid:string, change_amount:string}
     */
    public function calculate(array $input, bool $requirePaidInFull): array
    {
        $items = $input['items'] ?? [];
        $payments = $input['payments'] ?? [];

        $totalCents = 0;
        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (string) ($item['unit_price'] ?? '');

            if ($quantity <= 0) {
                throw new \InvalidArgumentException('item quantity must be greater than zero');
            }

            $unitCents = $this->moneyToCents($unitPrice);
            $totalCents += $quantity * $unitCents;
        }

        $totalPaidCents = 0;
        $hasCashPayment = false;

        foreach ($payments as $payment) {
            $amount = (string) ($payment['amount'] ?? '');
            $isCash = (bool) ($payment['is_cash'] ?? false);

            $amountCents = $this->moneyToCents($amount);
            $totalPaidCents += $amountCents;
            $hasCashPayment = $hasCashPayment || $isCash;
        }

        if ($requirePaidInFull && $totalPaidCents < $totalCents) {
            throw new \DomainException('total paid must be greater than or equal to total');
        }

        $changeCents = 0;
        if ($totalPaidCents > $totalCents) {
            if (!$hasCashPayment) {
                throw new \DomainException('overpayment is only allowed when there is a cash payment method');
            }

            $changeCents = $totalPaidCents - $totalCents;
        }

        return [
            'total' => $this->centsToMoney($totalCents),
            'total_paid' => $this->centsToMoney($totalPaidCents),
            'change_amount' => $this->centsToMoney($changeCents),
        ];
    }

    private function moneyToCents(string $value): int
    {
        $normalized = trim($value);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('amount must be a decimal value with up to 2 decimal places');
        }

        if (!str_contains($normalized, '.')) {
            $normalized .= '.00';
        }

        [$integer, $decimal] = explode('.', $normalized, 2);
        $decimal = str_pad($decimal, 2, '0');

        $cents = ((int) $integer * 100) + (int) $decimal;
        if ($cents <= 0) {
            throw new \InvalidArgumentException('amount must be greater than zero');
        }

        return $cents;
    }

    private function centsToMoney(int $cents): string
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('cents must be non-negative');
        }

        $integer = intdiv($cents, 100);
        $decimal = $cents % 100;

        return $integer . '.' . str_pad((string) $decimal, 2, '0', STR_PAD_LEFT);
    }
}

