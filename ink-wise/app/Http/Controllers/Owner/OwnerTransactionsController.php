<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class OwnerTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $status = strtolower((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $statusGroups = [
            'paid' => ['paid', 'complete', 'completed', 'settled'],
            'pending' => ['pending', 'processing', 'unpaid', 'awaiting', 'awaiting payment'],
            'failed' => ['failed', 'cancelled', 'canceled', 'refunded', 'void', 'declined'],
        ];

        $query = Payment::query()
            ->with([
                'customer',
                'order.customer',
                'order.customerOrder.customer',
            ]);

        if ($status !== '' && $status !== 'all') {
            $normalizedAllowed = collect($statusGroups[$status] ?? [$status])
                ->map(fn ($value) => strtolower($value))
                ->unique()
                ->values()
                ->all();

            $query->where(function ($statusQuery) use ($normalizedAllowed) {
                foreach ($normalizedAllowed as $index => $value) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $statusQuery->{$method}('LOWER(status) = ?', [$value]);
                }
            });
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $like = '%' . $search . '%';
                $inner->where('provider_payment_id', 'like', $like)
                    ->orWhere('intent_id', 'like', $like)
                    ->orWhere('method', 'like', $like)
                    ->orWhere('mode', 'like', $like)
                    ->orWhereHas('order', function ($orderQuery) use ($like) {
                        $orderQuery->where('id', 'like', $like)
                            ->orWhere('order_number', 'like', $like);
                    })
                    ->orWhereHas('customer', function ($customerQuery) use ($like) {
                        $customerQuery
                            ->where('name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE ?", [$like]);
                    })
                    ->orWhereHas('order.customer', function ($customerQuery) use ($like) {
                        $customerQuery
                            ->where('name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE ?", [$like]);
                    })
                    ->orWhereHas('order.customerOrder.customer', function ($customerQuery) use ($like) {
                        $customerQuery
                            ->where('name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE ?", [$like]);
                    });
            });
        }

        $transactions = $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $statusCase = static function (array $values): string {
            return collect($values)
                ->map(fn ($value) => "'" . strtolower($value) . "'")
                ->implode(', ');
        };

        $summary = Payment::query()->selectRaw(<<<SQL
            COUNT(*) as total_transactions,
            COALESCE(SUM(amount), 0) as total_amount,
            SUM(CASE WHEN LOWER(status) IN ({$statusCase($statusGroups['paid'])}) THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN LOWER(status) IN ({$statusCase($statusGroups['pending'])}) THEN 1 ELSE 0 END) as pending_count
        SQL)->first();

        return view('owner.transactions-view', [
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => (int) ($summary->total_transactions ?? 0),
                'total_amount' => (float) ($summary->total_amount ?? 0),
                'paid_count' => (int) ($summary->paid_count ?? 0),
                'pending_count' => (int) ($summary->pending_count ?? 0),
            ],
        ]);
    }
}
