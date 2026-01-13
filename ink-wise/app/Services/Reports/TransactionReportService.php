<?php

namespace App\Services\Reports;

use App\Models\Payment;
use App\Support\Owner\TransactionPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionReportService
{
    public function buildTransactionsQuery(
        Request $request,
        array $statusGroups,
        bool $applyStatusFilter = true,
        ?string $statusValue = null
    ): Builder {
        $status = Str::lower((string) ($statusValue ?? $request->query('status', '')));
        if (!$applyStatusFilter) {
            $status = '';
        }

        $search = trim((string) $request->query('search', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $query = Payment::query()->with([
            'customer.user',
            'order.customer.user',
            'order.customerOrder.customer.user',
        ]);

        // Exclude payments that have been archived themselves
        $query->where(function ($q) {
            $q->whereNull('archived')->orWhere('archived', false);
        });

        if ($status !== '' && $status !== 'all') {
            $allowedStatuses = collect($statusGroups[$status] ?? [$status])
                ->map(fn ($value) => Str::lower($value))
                ->unique()
                ->values()
                ->all();

            if (!empty($allowedStatuses)) {
                $query->whereIn(DB::raw('LOWER(status)'), $allowedStatuses);
            }
        }

        // Date range filtering
        if ($dateFrom !== '') {
            $query->whereDate('recorded_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('recorded_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $terms = collect(preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY));
            if ($terms->isEmpty()) {
                $terms = collect([$search]);
            }

            $query->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $numericTerm = preg_replace('/[^0-9.]/', '', $term);

                    $outer->where(function ($inner) use ($like, $numericTerm) {
                        $inner->where(function ($paymentFieldQuery) use ($like, $numericTerm) {
                            $paymentFieldQuery
                                ->where('provider_payment_id', 'like', $like)
                                ->orWhere('intent_id', 'like', $like)
                                ->orWhere('method', 'like', $like)
                                ->orWhere('mode', 'like', $like)
                                ->orWhere('provider', 'like', $like)
                                ->orWhere('currency', 'like', $like)
                                ->orWhere('status', 'like', $like);

                            if ($numericTerm !== '') {
                                $paymentFieldQuery
                                    ->orWhereRaw('CAST(amount AS CHAR) LIKE ?', ['%' . $numericTerm . '%'])
                                    ->orWhereRaw('DATE_FORMAT(recorded_at, "%Y-%m-%d") LIKE ?', ['%' . $numericTerm . '%'])
                                    ->orWhereRaw('DATE_FORMAT(created_at, "%Y-%m-%d") LIKE ?', ['%' . $numericTerm . '%']);
                            }
                        })
                            ->orWhereHas('order', function (Builder $orderQuery) use ($like, $numericTerm) {
                                $orderQuery->where('order_number', 'like', $like)
                                    ->orWhere('status', 'like', $like)
                                    ->orWhere('payment_method', 'like', $like)
                                    ->orWhere('payment_status', 'like', $like)
                                    ->orWhere('shipping_option', 'like', $like)
                                    ->orWhere('metadata', 'like', $like)
                                    ->orWhere('summary_snapshot', 'like', $like);

                                if ($numericTerm !== '') {
                                    $orderQuery
                                        ->orWhereRaw('CAST(total_amount AS CHAR) LIKE ?', ['%' . $numericTerm . '%'])
                                        ->orWhereRaw('CAST(subtotal_amount AS CHAR) LIKE ?', ['%' . $numericTerm . '%'])
                                        ->orWhereRaw('CAST(tax_amount AS CHAR) LIKE ?', ['%' . $numericTerm . '%'])
                                        ->orWhereRaw('CAST(shipping_fee AS CHAR) LIKE ?', ['%' . $numericTerm . '%'])
                                        ->orWhereRaw('DATE_FORMAT(order_date, "%Y-%m-%d") LIKE ?', ['%' . $numericTerm . '%'])
                                        ->orWhereRaw('DATE_FORMAT(date_needed, "%Y-%m-%d") LIKE ?', ['%' . $numericTerm . '%']);
                                }
                            })
                            ->orWhereHas('customer', $this->customerLikeCallback($like))
                            ->orWhereHas('order.customer', $this->customerLikeCallback($like))
                            ->orWhereHas('order.customerOrder.customer', $this->customerLikeCallback($like));
                    });
                }
            });
        }

        // Exclude payments that belong to orders which are pending payment or already archived.
        // Keep payments that have no order relationship.
        $query->where(function ($q) {
            $q->whereDoesntHave('order')
                ->orWhereHas('order', function (Builder $orderQ) {
                    $orderQ->where(DB::raw('LOWER(payment_status)'), '<>', 'pending')
                        ->where(function (Builder $sub) {
                            $sub->where('archived', 0)->orWhereNull('archived');
                        });
                });
        });

        return $query;
    }

    public function summarize(Builder $query, array $statusGroups): array
    {
        $totalTransactions = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');

        $summary = [
            'total_transactions' => (int) $totalTransactions,
            'total_amount' => (float) $totalAmount,
        ];

        foreach ($statusGroups as $key => $statuses) {
            $normalized = collect($statuses)
                ->map(fn ($value) => Str::lower($value))
                ->unique()
                ->values()
                ->all();

            $summary["{$key}_count"] = $this->countByStatuses(clone $query, $normalized);
            $summary["{$key}_amount"] = $this->sumByStatuses(clone $query, $normalized);
        }

        return $summary;
    }

    public function paginate(Builder $query, Request $request, int $perPage = 25): LengthAwarePaginator
    {
        return $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function transform(iterable $transactions): Collection
    {
        return TransactionPresenter::transformCollection($transactions);
    }

    public function transformAll(Builder $query): Collection
    {
        $rows = $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();

        return TransactionPresenter::transformCollection($rows);
    }

    protected function countByStatuses(Builder $query, array $statuses): int
    {
        if (empty($statuses)) {
            return 0;
        }

        return (clone $query)->whereIn(DB::raw('LOWER(status)'), $statuses)->count();
    }

    protected function sumByStatuses(Builder $query, array $statuses): float
    {
        if (empty($statuses)) {
            return 0.0;
        }

        return (float) (clone $query)
            ->whereIn(DB::raw('LOWER(status)'), $statuses)
            ->sum('amount');
    }

    protected function customerLikeCallback(string $like): \Closure
    {
        return function (Builder $customerQuery) use ($like) {
            $customerQuery->where(function (Builder $sub) use ($like) {
                $sub->where('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhereRaw("CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE ?", [$like])
                    ->orWhereRaw("CONCAT(TRIM(first_name), ' ', TRIM(middle_name), ' ', TRIM(last_name)) LIKE ?", [$like])
                    ->orWhereHas('user', function (Builder $userQuery) use ($like) {
                        $userQuery->where('email', 'like', $like);
                    });
            });
        };
    }
}
