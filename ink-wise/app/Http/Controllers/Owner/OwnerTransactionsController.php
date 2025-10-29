<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Owner\TransactionPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $statusGroups = TransactionPresenter::statusGroups();

        $transactionsQuery = $this->buildTransactionsQuery($request, $statusGroups);

        $transactions = $transactionsQuery
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $transformedRows = $this->transformForView($transactions);
        $summary = $this->buildSummary($statusGroups);

        return view('owner.transactions-view', [
            'transactions' => $transactions,
            'summary' => $summary,
            'statusGroups' => $statusGroups,
            'transformedRows' => $transformedRows,
        ]);
    }

    public function export(Request $request)
    {
        $format = Str::lower((string) $request->query('format', 'csv'));
        $statusGroups = TransactionPresenter::statusGroups();

        $rows = $this->buildTransactionsQuery($request, $statusGroups)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();

        $transformed = TransactionPresenter::transformCollection($rows);

        $exportRows = $transformed->map(function (array $row) {
            return [
                'transaction_id' => $row['transaction_id'],
                'order_id' => $row['order_id'],
                'customer' => $row['customer_name'],
                'payment_method' => $row['payment_method'],
                'date' => $row['display_date'],
                'amount' => $row['amount_display'],
                'status' => $row['status_label'],
            ];
        });

        if ($format !== 'csv') {
            abort(400, 'Unsupported export format.');
        }

        $fileName = 'transactions-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($exportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Transaction ID', 'Order ID', 'Customer', 'Payment Method', 'Date', 'Amount', 'Status']);

            foreach ($exportRows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    protected function buildTransactionsQuery(Request $request, array $statusGroups): Builder
    {
        $status = Str::lower((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $query = Payment::query()->with([
            'customer.user',
            'order.customer.user',
            'order.customerOrder.customer.user',
        ]);

        if ($status !== '' && $status !== 'all') {
            $allowedStatuses = collect($statusGroups[$status] ?? [$status])
                ->map(fn ($value) => Str::lower($value))
                ->unique()
                ->values()
                ->all();

            $query->where(function ($statusQuery) use ($allowedStatuses) {
                foreach ($allowedStatuses as $index => $value) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $statusQuery->{$method}('LOWER(status) = ?', [$value]);
                }
            });
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
                            ->orWhereHas('order', function ($orderQuery) use ($like, $numericTerm) {
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

        return $query;
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

    protected function buildSummary(array $statusGroups): array
    {
        $statusCase = static function (array $values): string {
            return collect($values)
                ->map(fn ($value) => "'" . Str::lower($value) . "'")
                ->implode(', ');
        };

        $summary = Payment::query()->selectRaw(<<<SQL
            COUNT(*) as total_transactions,
            COALESCE(SUM(amount), 0) as total_amount,
            SUM(CASE WHEN LOWER(status) IN ({$statusCase($statusGroups['paid'])}) THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN LOWER(status) IN ({$statusCase($statusGroups['pending'])}) THEN 1 ELSE 0 END) as pending_count
        SQL)->first();

        return [
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'total_amount' => (float) ($summary->total_amount ?? 0),
            'paid_count' => (int) ($summary->paid_count ?? 0),
            'pending_count' => (int) ($summary->pending_count ?? 0),
        ];
    }

    protected function transformForView(LengthAwarePaginator $paginator): Collection
    {
        return TransactionPresenter::transformCollection($paginator->items());
    }
}
