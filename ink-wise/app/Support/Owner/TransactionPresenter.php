<?php

namespace App\Support\Owner;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransactionPresenter
{
    public const STATUS_GROUPS = [
        'paid' => ['paid', 'complete', 'completed', 'settled'],
        'pending' => ['pending', 'processing', 'unpaid', 'awaiting', 'awaiting payment'],
        'failed' => ['failed', 'cancelled', 'canceled', 'refunded', 'void', 'declined'],
    ];

    public static function statusGroups(): array
    {
        return self::STATUS_GROUPS;
    }

    public static function normalizeStatus(string $value): string
    {
        return Str::lower(trim($value));
    }

    public static function resolveBadgeClass(string $status): string
    {
        $status = self::normalizeStatus($status);

        if (in_array($status, self::STATUS_GROUPS['paid'], true)) {
            return 'stock-ok';
        }

        if (in_array($status, self::STATUS_GROUPS['failed'], true)) {
            return 'stock-critical';
        }

        return 'stock-low';
    }

    public static function transform($transaction): array
    {
        $transactionId = data_get($transaction, 'transaction_id')
            ?? data_get($transaction, 'provider_payment_id')
            ?? data_get($transaction, 'intent_id')
            ?? data_get($transaction, 'reference')
            ?? data_get($transaction, 'id')
            ?? '—';
        $transactionId = is_string($transactionId)
            ? trim($transactionId)
            : (is_numeric($transactionId) ? (string) $transactionId : '—');

        $orderId = data_get($transaction, 'order_id')
            ?? data_get($transaction, 'order.reference')
            ?? data_get($transaction, 'order.order_number')
            ?? data_get($transaction, 'order.id');
        if (is_array($orderId)) {
            $orderId = $orderId['order_number'] ?? $orderId['id'] ?? null;
        }
        if ($orderId instanceof \DateTimeInterface) {
            $orderId = $orderId->format('Y-m-d');
        }
        if (is_numeric($orderId)) {
            $orderId = '#' . ltrim((string) $orderId, '#');
        }
        $orderId = $orderId ? (string) $orderId : '—';

        $customerName = data_get($transaction, 'customer_name')
            ?? data_get($transaction, 'customer.name')
            ?? data_get($transaction, 'customer.full_name')
            ?? data_get($transaction, 'order.customer.name')
            ?? data_get($transaction, 'order.customer.full_name')
            ?? data_get($transaction, 'order.customerOrder.customer.name');

        if (!$customerName) {
            $customerSource = data_get($transaction, 'customer');
            if (!$customerSource) {
                $customerSource = data_get($transaction, 'order.customer')
                    ?? data_get($transaction, 'order.customerOrder.customer');
            }

            if (is_array($customerSource)) {
                $customerName = $customerSource['name']
                    ?? trim(($customerSource['first_name'] ?? '') . ' ' . ($customerSource['last_name'] ?? ''));
            } elseif (is_object($customerSource)) {
                $customerName = $customerSource->name
                    ?? $customerSource->full_name
                    ?? trim(($customerSource->first_name ?? '') . ' ' . ($customerSource->last_name ?? ''));
                if (!$customerName && method_exists($customerSource, '__toString')) {
                    $customerName = (string) $customerSource;
                }
            } elseif (is_string($customerSource)) {
                $customerName = $customerSource;
            }
        }
        $customerName = $customerName ? trim((string) $customerName) : '—';

        $paymentMethod = data_get($transaction, 'payment_method')
            ?? data_get($transaction, 'method')
            ?? data_get($transaction, 'mode')
            ?? data_get($transaction, 'payment.method');
        if (is_array($paymentMethod)) {
            $paymentMethod = $paymentMethod['name'] ?? $paymentMethod['label'] ?? null;
        }
        $paymentMethod = $paymentMethod ? trim((string) $paymentMethod) : '—';

        $rawDate = data_get($transaction, 'date')
            ?? data_get($transaction, 'paid_at')
            ?? data_get($transaction, 'recorded_at')
            ?? data_get($transaction, 'created_at')
            ?? data_get($transaction, 'updated_at');
        if ($rawDate instanceof \DateTimeInterface) {
            $displayDate = $rawDate->format('Y-m-d');
        } elseif (is_string($rawDate) && strlen($rawDate) >= 10) {
            $displayDate = substr($rawDate, 0, 10);
        } else {
            $displayDate = '—';
        }

        $amountValue = data_get($transaction, 'amount') ?? data_get($transaction, 'total');
        $currency = strtoupper((string) data_get($transaction, 'currency', 'PHP'));
        $currencyPrefix = $currency === 'PHP'
            ? '₱'
            : ($currency !== '' ? $currency . ' ' : '');

        if (is_numeric($amountValue)) {
            $amountNumeric = (float) $amountValue;
            $amountDisplay = $currencyPrefix . number_format($amountNumeric, 2);
        } elseif (is_string($amountValue) && trim($amountValue) !== '') {
            $amountNumeric = null;
            $amountDisplay = trim($amountValue);
        } else {
            $amountNumeric = null;
            $amountDisplay = '—';
        }

        $statusRaw = self::normalizeStatus((string) data_get($transaction, 'status', ''));
        $statusLabel = $statusRaw !== ''
            ? Str::headline(str_replace('_', ' ', $statusRaw))
            : '—';
        $statusClass = $statusLabel === '—' ? null : self::resolveBadgeClass($statusRaw);

        $amountSearchTokens = [];
        if ($amountNumeric !== null) {
            $amountSearchTokens[] = number_format($amountNumeric, 2, '.', '');
            $amountSearchTokens[] = preg_replace('/[^0-9]/', '', number_format($amountNumeric, 2, '.', ''));
        }

        $searchTargets = array_filter([
            $transactionId,
            data_get($transaction, 'provider_payment_id'),
            data_get($transaction, 'intent_id'),
            $orderId,
            $customerName,
            $paymentMethod,
            $displayDate,
            $amountDisplay,
            $statusLabel,
            data_get($transaction, 'provider'),
            $currency,
            ...$amountSearchTokens,
        ], static fn ($value) => $value !== null && $value !== '');

        return [
            'raw' => $transaction,
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'payment_method' => $paymentMethod,
            'display_date' => $displayDate,
            'amount_display' => $amountDisplay,
            'amount_numeric' => $amountNumeric,
            'status_raw' => $statusRaw,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'currency' => $currency,
            'search_blob' => Str::lower(implode(' ', $searchTargets)),
        ];
    }

    public static function transformCollection(iterable $transactions): Collection
    {
        return collect($transactions)->map(function ($transaction) {
            return self::transform($transaction);
        });
    }

    public static function countByStatuses(Collection $rows, array $statuses): int
    {
        $statuses = array_map(fn ($value) => Str::lower($value), $statuses);

        return $rows->filter(function ($row) use ($statuses) {
            return in_array($row['status_raw'], $statuses, true);
        })->count();
    }
}
