<?php

namespace App\Services;

use App\Enums\SaleStatus;
use Dompdf\Dompdf;
use PDO;

final class SalePdfService
{
    public function __construct(private PDO $pdo) {}

    public function generate(int $saleId): string
    {
        $sale = $this->fetchSale($saleId);
        if (!$sale) {
            throw new \RuntimeException('Sale not found');
        }

        if (($sale['status'] ?? null) !== SaleStatus::FINALIZED->value) {
            throw new \DomainException('sale must be FINALIZED to generate pdf');
        }

        $items = $this->fetchItems($saleId);
        $payments = $this->fetchPayments($saleId);

        $html = $this->renderHtml($sale, $items, $payments);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function fetchSale(int $saleId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.total,
                s.total_paid,
                s.change_amount,
                s.status,
                s.created_at,
                s.customer_id,
                c.name AS customer_name,
                s.user_id,
                u.name AS user_name
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            JOIN users u ON u.id = s.user_id
            WHERE s.id = ?
        ");
        $stmt->execute([$saleId]);
        $data = $stmt->fetch();

        return $data ?: null;
    }

    private function fetchItems(int $saleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                si.product_id,
                p.sku,
                p.name,
                si.quantity,
                si.unit_price
            FROM sale_items si
            JOIN products p ON p.id = si.product_id
            WHERE si.sale_id = ?
            ORDER BY si.id ASC
        ");
        $stmt->execute([$saleId]);

        return $stmt->fetchAll() ?: [];
    }

    private function fetchPayments(int $saleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pay.payment_method_id,
                pm.name,
                pm.type,
                pay.amount
            FROM payments pay
            JOIN payment_methods pm ON pm.id = pay.payment_method_id
            WHERE pay.sale_id = ?
            ORDER BY pay.id ASC
        ");
        $stmt->execute([$saleId]);

        return $stmt->fetchAll() ?: [];
    }

    private function renderHtml(array $sale, array $items, array $payments): string
    {
        $escape = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $rowsItems = '';
        foreach ($items as $item) {
            $lineTotal = $this->mulMoney((string) $item['unit_price'], (int) $item['quantity']);

            $rowsItems .= '<tr>'
                . '<td>' . $escape($item['sku']) . '</td>'
                . '<td>' . $escape($item['name']) . '</td>'
                . '<td style="text-align:right;">' . $escape($item['quantity']) . '</td>'
                . '<td style="text-align:right;">' . $escape($item['unit_price']) . '</td>'
                . '<td style="text-align:right;">' . $escape($lineTotal) . '</td>'
                . '</tr>';
        }

        $rowsPayments = '';
        foreach ($payments as $payment) {
            $rowsPayments .= '<tr>'
                . '<td>' . $escape($payment['name']) . '</td>'
                . '<td>' . $escape($payment['type']) . '</td>'
                . '<td style="text-align:right;">' . $escape($payment['amount']) . '</td>'
                . '</tr>';
        }

        $customer = $sale['customer_name'] ? $escape($sale['customer_name']) : 'Walk-in';

        return '
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 18px; margin: 0 0 10px; }
    .meta { margin-bottom: 14px; }
    .meta div { margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #f3f3f3; text-align: left; }
    .totals { margin-top: 14px; width: 100%; }
    .totals td { border: none; padding: 2px 0; }
    .totals .label { text-align: right; padding-right: 12px; width: 85%; }
    .totals .value { text-align: right; width: 15%; }
  </style>
  <title>Sale #' . $escape($sale['id']) . '</title>
</head>
<body>
  <h1>Sale #' . $escape($sale['id']) . '</h1>
  <div class="meta">
    <div><strong>Date:</strong> ' . $escape($sale['created_at']) . '</div>
    <div><strong>Status:</strong> ' . $escape($sale['status']) . '</div>
    <div><strong>Customer:</strong> ' . $customer . '</div>
    <div><strong>Seller:</strong> ' . $escape($sale['user_name']) . '</div>
  </div>

  <h2 style="font-size:14px;margin:0;">Items</h2>
  <table>
    <thead>
      <tr>
        <th>SKU</th>
        <th>Product</th>
        <th style="text-align:right;">Qty</th>
        <th style="text-align:right;">Unit</th>
        <th style="text-align:right;">Total</th>
      </tr>
    </thead>
    <tbody>' . $rowsItems . '</tbody>
  </table>

  <h2 style="font-size:14px;margin:16px 0 0;">Payments</h2>
  <table>
    <thead>
      <tr>
        <th>Method</th>
        <th>Type</th>
        <th style="text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>' . $rowsPayments . '</tbody>
  </table>

  <table class="totals">
    <tr><td class="label"><strong>Total</strong></td><td class="value"><strong>' . $escape($sale['total']) . '</strong></td></tr>
    <tr><td class="label">Total paid</td><td class="value">' . $escape($sale['total_paid']) . '</td></tr>
    <tr><td class="label">Change</td><td class="value">' . $escape($sale['change_amount']) . '</td></tr>
  </table>
</body>
</html>';
    }

    private function mulMoney(string $money, int $quantity): string
    {
        $money = trim($money);
        if (!str_contains($money, '.')) {
            $money .= '.00';
        }

        [$i, $d] = explode('.', $money, 2);
        $d = str_pad($d, 2, '0');
        $cents = ((int) $i * 100) + (int) $d;

        $total = $cents * $quantity;
        $integer = intdiv($total, 100);
        $decimal = $total % 100;

        return $integer . '.' . str_pad((string) $decimal, 2, '0', STR_PAD_LEFT);
    }
}

