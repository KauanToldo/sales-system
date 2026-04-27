<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SaleCalculator;
use PHPUnit\Framework\TestCase;

final class SaleCalculatorTest extends TestCase
{
    public function test_calculates_totals_without_change(): void
    {
        $calculator = new SaleCalculator();

        $result = $calculator->calculate([
            'items' => [
                ['quantity' => 2, 'unit_price' => '10.50'],
                ['quantity' => 1, 'unit_price' => '5.00'],
            ],
            'payments' => [
                ['amount' => '20.00', 'is_cash' => false],
            ],
        ], false);

        self::assertSame('26.00', $result['total']);
        self::assertSame('20.00', $result['total_paid']);
        self::assertSame('0.00', $result['change_amount']);
    }

    public function test_returns_change_when_overpayment_has_cash(): void
    {
        $calculator = new SaleCalculator();

        $result = $calculator->calculate([
            'items' => [
                ['quantity' => 1, 'unit_price' => '15.00'],
            ],
            'payments' => [
                ['amount' => '20.00', 'is_cash' => true],
            ],
        ], true);

        self::assertSame('5.00', $result['change_amount']);
    }

    public function test_throws_when_overpayment_has_no_cash_method(): void
    {
        $calculator = new SaleCalculator();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('overpayment is only allowed when there is a cash payment method');

        $calculator->calculate([
            'items' => [
                ['quantity' => 1, 'unit_price' => '10.00'],
            ],
            'payments' => [
                ['amount' => '15.00', 'is_cash' => false],
            ],
        ], true);
    }

    public function test_throws_when_finalizing_with_insufficient_payment(): void
    {
        $calculator = new SaleCalculator();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('total paid must be greater than or equal to total');

        $calculator->calculate([
            'items' => [
                ['quantity' => 2, 'unit_price' => '10.00'],
            ],
            'payments' => [
                ['amount' => '5.00', 'is_cash' => true],
            ],
        ], true);
    }
}
