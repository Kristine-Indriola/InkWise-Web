<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OwnerOrderWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $payload = $this->buildPagePayload($request);

        return view('owner.order-workflow', $payload);
    }

    public function data(Request $request)
    {
        $payload = $this->buildPagePayload($request);

        return response()->json([
            'counts' => $payload['counts'],
            'orders' => $payload['orders'],
            'filters' => $payload['filters'],
            'generated_at' => now()->timezone($this->timezone())->toIso8601String(),
        ]);
    }

    public function archived(Request $request)
    {
        $filters = $this->normalizeFilters($request);

        $orders = $this->queryOrders($filters, true)
            ->map(fn (Order $order) => $this->formatArchivedOrderForDisplay($order))
            ->values()
            ->all();

        return view('owner.order-archived', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    protected function buildPagePayload(Request $request): array
    {
        $filters = $this->normalizeFilters($request);

        $orders = $this->queryOrders($filters)
            ->map(fn (Order $order) => $this->formatOrderForDisplay($order))
            ->values()
            ->all();

        return [
            'orders' => $orders,
            'counts' => $this->buildSummaryCounts(),
            'filters' => $filters,
        ];
    }

    protected function normalizeFilters(Request $request): array
    {
        $status = Str::lower((string) $request->query('status', ''));
        $status = $status === 'all' ? null : ($status ?: null);

        $search = trim((string) $request->query('search', ''));
        $limit = (int) $request->query('limit', 50);
        $limit = max(10, min($limit, 200));

        return [
            'status' => $status,
            'search' => $search,
            'limit' => $limit,
        ];
    }

    protected function queryOrders(array $filters, bool $archivedOnly = false): Collection
    {
        $query = Order::query()
            ->with([
                'customer',
                'customerOrder',
                'items.product',
                'activities' => fn ($relation) => $relation->latest()->limit(1),
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        $query->where(function (Builder $builder) use ($archivedOnly) {
            if ($archivedOnly) {
                $builder->where('archived', true);
            } else {
                $builder->whereNull('archived')->orWhere('archived', false);
            }
        });

        if ($filters['status']) {
            $statuses = $this->expandStatusFilter($filters['status']);

            if (!empty($statuses)) {
                $query->where(function (Builder $builder) use ($statuses) {
                    foreach ($statuses as $index => $value) {
                        if ($index === 0) {
                            $builder->whereRaw('LOWER(COALESCE(status, \'\')) = ?', [$value]);
                        } else {
                            $builder->orWhereRaw('LOWER(COALESCE(status, \'\')) = ?', [$value]);
                        }
                    }
                });
            }
        }

        if ($filters['search']) {
            $rawSearch = $filters['search'];
            $keyword = Str::lower($rawSearch);
            $orderNumberKeyword = Str::lower(ltrim($rawSearch, '#'));
            $like = '%' . $keyword . '%';
            $orderNumberLike = '%' . $orderNumberKeyword . '%';
            $numeric = preg_replace('/\D+/', '', $rawSearch);
            $searchDate = $this->detectSearchDate($rawSearch);

            $query->where(function (Builder $builder) use ($like, $orderNumberLike, $numeric, $searchDate) {
                $builder->whereRaw("LOWER(COALESCE(order_number, '')) LIKE ?", [$orderNumberLike])
                    ->orWhereRaw('LOWER(CAST(id AS CHAR)) LIKE ?', [$like]);

                if ($numeric !== '') {
                    $builder->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%' . $numeric . '%']);
                }

                if ($searchDate) {
                    $builder->orWhereDate('order_date', $searchDate->toDateString())
                        ->orWhereDate('created_at', $searchDate->toDateString());
                }

                $builder->orWhereRaw("LOWER(COALESCE(status, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(payment_status, '')) LIKE ?", [$like]);

                $builder->orWhereHas('customer', function (Builder $relation) use ($like) {
                    $relation->whereRaw("LOWER(TRIM(CONCAT_WS(' ', NULLIF(first_name, ''), NULLIF(middle_name, ''), NULLIF(last_name, '')))) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(first_name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(last_name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(contact_number, '')) LIKE ?", [$like]);
                });

                $builder->orWhereHas('customer.user', function (Builder $relation) use ($like) {
                    $relation->whereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$like]);
                });

                $builder->orWhereHas('customerOrder', function (Builder $relation) use ($like) {
                    $relation->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(company, '')) LIKE ?", [$like]);
                });

                $builder->orWhereHas('items', function (Builder $relation) use ($like) {
                    $relation->whereRaw("LOWER(COALESCE(product_name, '')) LIKE ?", [$like]);
                });

                $builder->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(summary_snapshot, '{}'), '$.primary_item.name'))) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(summary_snapshot, '{}'), '$.primary_item.product_name'))) LIKE ?", [$like]);
            });
        }

        return $query
            ->limit($filters['limit'])
            ->get();
    }

    protected function expandStatusFilter(?string $status): array
    {
        if (!$status) {
            return [];
        }

        return match ($status) {
            'pending' => ['pending', 'processing', 'to_receive'],
            'in_production' => ['in_production'],
            'confirmed' => ['confirmed', 'completed'],
            default => [$status],
        };
    }

    protected function buildSummaryCounts(): array
    {
        $row = Order::query()
            ->where(function (Builder $builder) {
                $builder->whereNull('archived')->orWhere('archived', false);
            })
            ->selectRaw(<<<SQL
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('confirmed', 'completed') THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN LOWER(COALESCE(status, '')) IN ('pending', 'processing', 'to_receive') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'in_production' THEN 1 ELSE 0 END) as in_production,
            SUM(CASE WHEN LOWER(COALESCE(status, '')) = 'cancelled' THEN 1 ELSE 0 END) as cancelled
SQL
        )->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'confirmed' => (int) ($row->confirmed ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'in_production' => (int) ($row->in_production ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
        ];
    }

    protected function detectSearchDate(string $value): ?Carbon
    {
        $candidate = trim($value);

        if ($candidate === '') {
            return null;
        }

        $formats = [
            'Y-m-d',
            'm/d/Y',
            'm/d/y',
            'n/j/Y',
            'n/j/y',
            'M j, Y',
            'M j Y',
            'F j, Y',
            'F j Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $candidate, $this->timezone());
            } catch (\Throwable $exception) {
                $parsed = false;
            }

            if ($parsed !== false) {
                return $parsed;
            }
        }

        try {
            return Carbon::parse($candidate, $this->timezone());
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function formatOrderForDisplay(Order $order): array
    {
        $status = Str::lower((string) ($order->status ?? 'pending'));

        return [
            'id' => $order->id,
            'order_number' => $this->formatOrderNumber($order),
            'customer_name' => $this->resolveCustomerName($order),
            'ordered_at' => $this->formatOrderDate($order),
            'summary' => $this->summarizeOrder($order),
            'total' => $this->formatOrderTotal($order),
            'status' => $status,
            'status_label' => $this->formatStatusLabel($status),
            'status_badge_class' => $this->statusBadgeClass($status),
        ];
    }

    protected function formatOrderNumber(Order $order): string
    {
        $number = $order->order_number ?: sprintf('%04d', $order->id);

        return Str::startsWith($number, '#') ? $number : '#' . ltrim($number, '#');
    }

    protected function resolveCustomerName(Order $order): string
    {
        $customer = $order->customer;
        $customerOrder = $order->customerOrder;

        if ($customer && !empty($customer->name)) {
            return $customer->name;
        }

        if ($customerOrder && !empty($customerOrder->name)) {
            return $customerOrder->name;
        }

        if ($customer) {
            $parts = array_filter([
                $customer->first_name ?? null,
                $customer->middle_name ?? null,
                $customer->last_name ?? null,
            ]);

            if (!empty($parts)) {
                return implode(' ', $parts);
            }

            if (!empty($customer->email)) {
                return $customer->email;
            }
        }

        if ($customerOrder && !empty($customerOrder->email)) {
            return $customerOrder->email;
        }

        return 'Guest customer';
    }

    protected function formatOrderDate(Order $order): string
    {
        $date = $order->order_date ?: $order->created_at;

        if (!$date) {
            return '—';
        }

        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return $date->timezone($this->timezone())->format('M d, Y');
    }

    protected function summarizeOrder(Order $order): string
    {
        $snapshot = $order->summary_snapshot ?? [];
        $primary = Arr::get($snapshot, 'primary_item');

        if (!is_array($primary)) {
            $primary = Arr::get($snapshot, 'items.0', []);
        }

        $name = Arr::get($primary, 'name')
            ?? Arr::get($primary, 'product_name')
            ?? Arr::get($primary, 'label');

        $quantity = Arr::get($primary, 'quantity')
            ?? Arr::get($primary, 'qty')
            ?? Arr::get($primary, 'pieces');

        if (!$name && $order->relationLoaded('items')) {
            $item = $order->items->first();

            if ($item) {
                $name = $item->product_name ?? ($item->product?->name ?? null);
                $quantity = $quantity ?? $item->quantity;
            }
        }

        $quantity = $quantity !== null ? max((int) $quantity, 1) : null;

        if ($name && $quantity) {
            return sprintf('%s — %s pcs', $name, number_format($quantity));
        }

        if ($name) {
            return $name;
        }

        if ($order->relationLoaded('items') && $order->items->isNotEmpty()) {
            $count = $order->items->count();
            return $count === 1 ? '1 line item' : sprintf('%d line items', $count);
        }

        return 'View details';
    }

    protected function formatStatusLabel(string $status): string
    {
        $normalized = Str::lower(trim($status));

        if ($normalized === '') {
            return 'Pending';
        }

        $labels = [
            'draft' => 'New Order',
        ];

        if (array_key_exists($normalized, $labels)) {
            return $labels[$normalized];
        }

        return Str::of($normalized)->replace('_', ' ')->headline();
    }

    protected function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'confirmed', 'completed' => 'badge stock-ok',
            'pending', 'in_production', 'processing', 'to_receive' => 'badge stock-low',
            'cancelled' => 'badge stock-critical',
            default => 'badge',
        };
    }

    protected function formatArchivedOrderForDisplay(Order $order): array
    {
        $status = Str::lower((string) ($order->status ?? 'pending'));
        $paymentStatus = Str::lower((string) ($order->payment_status ?? 'pending'));
        $archiveMeta = $this->resolveArchiveMeta($order);

        return [
            'id' => $order->id,
            'order_number' => $this->formatOrderNumber($order),
            'customer_name' => $this->resolveCustomerName($order),
            'items_count' => $this->countOrderItems($order),
            'total' => $this->formatOrderTotal($order),
            'payment_status' => $paymentStatus,
            'payment_label' => $this->formatPaymentStatusLabel($paymentStatus),
            'payment_badge_class' => $this->paymentBadgeClass($paymentStatus),
            'status' => $status,
            'status_label' => $this->formatStatusLabel($status),
            'status_badge_class' => $this->statusBadgeClass($status),
            'ordered_at' => $this->formatOrderDate($order),
            'archived_by' => $archiveMeta['by'],
            'archived_at' => $archiveMeta['at'],
        ];
    }

    protected function countOrderItems(Order $order): int
    {
        if ($order->relationLoaded('items')) {
            $quantity = (int) $order->items->sum(fn ($item) => (int) ($item->quantity ?? 0));

            if ($quantity > 0) {
                return $quantity;
            }

            return $order->items->count();
        }

        return (int) ($order->items_count ?? 0);
    }

    protected function resolveArchiveMeta(Order $order): array
    {
        $activity = $order->relationLoaded('activities') ? $order->activities->first() : null;
        $by = 'System';
        $timestamp = null;

        if ($activity) {
            $by = $activity->user_name ?? $by;
            $timestamp = $activity->created_at ?? null;
        }

        return [
            'by' => $by,
            'at' => $this->formatArchiveTimestamp($timestamp),
        ];
    }

    protected function formatArchiveTimestamp(?Carbon $timestamp): string
    {
        if (!$timestamp) {
            return '—';
        }

        if (!$timestamp instanceof Carbon) {
            $timestamp = Carbon::parse($timestamp, $this->timezone());
        }

        return $timestamp->timezone($this->timezone())->format('M d, Y g:i A');
    }

    protected function formatPaymentStatusLabel(string $status): string
    {
        $normalized = Str::lower(trim($status));

        if ($normalized === '') {
            return 'Pending';
        }

        return Str::of($normalized)->replace('_', ' ')->headline();
    }

    protected function paymentBadgeClass(string $status): string
    {
        return match ($status) {
            'paid' => 'badge stock-ok',
            'partial', 'processing', 'pending' => 'badge stock-low',
            'failed', 'cancelled', 'refunded' => 'badge stock-critical',
            default => 'badge',
        };
    }

    protected function formatOrderTotal(Order $order): string
    {
        $amount = (float) ($order->total_amount ?? 0);

        return number_format($amount, 2);
    }

    protected function timezone(): string
    {
        return config('app.timezone', 'UTC');
    }

    public function pickupCalendar(Request $request)
    {
        $period = $request->input('period', 'week'); // day, week, current_month, month, year

        $now = Carbon::now();

        switch ($period) {
            case 'day':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'current_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'month':
                $nextMonth = $now->copy()->addMonth();
                $start = $nextMonth->copy()->startOfMonth();
                $end = $nextMonth->copy()->endOfMonth();
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            default:
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
        }

        $orders = Order::query()
            ->with(['customer:customer_id,first_name,last_name', 'items:id,order_id,product_name,quantity'])
            ->whereNotNull('date_needed')
            ->where('date_needed', '>=', $start)
            ->where('date_needed', '<=', $end)
            ->orderBy('date_needed')
            ->get()
            ->groupBy(function (Order $order) {
                return $order->date_needed->format('Y-m-d');
            })
            ->map(function (Collection $dayOrders) {
                return $dayOrders->map(function (Order $order) {
                    $customer = $order->customer;
                    $customerName = collect([
                        optional($customer)->first_name,
                        optional($customer)->last_name,
                    ])->filter()->implode(' ');

                    if (trim($customerName) === '') {
                        $customerName = optional($order->customerOrder)->name ?? '-';
                    }

                    return [
                        'id' => $order->id,
                        'inv' => $order->order_number ?? ('#' . $order->id),
                        'customer_name' => $customerName,
                        'total_amount' => (float) $order->total_amount,
                        'items_count' => $order->items->sum('quantity'),
                        'items_list' => $order->items->pluck('product_name')->filter()->implode(', '),
                        'date_needed' => $order->date_needed->format('Y-m-d H:i:s'),
                        'status' => $order->status,
                    ];
                });
            });

        if ($period === 'year') {
            // For year view, group by month instead of individual days
            $calendarData = [];
            $current = $start->copy();

            while ($current <= $end) {
                $monthKey = $current->format('Y-m');
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                $monthOrders = collect();
                $monthTotalAmount = 0;
                $monthTotalOrders = 0;

                // Collect all orders for this month
                while ($monthStart <= $monthEnd) {
                    $dayKey = $monthStart->format('Y-m-d');
                    if ($orders->has($dayKey)) {
                        $dayOrders = $orders->get($dayKey);
                        $monthOrders = $monthOrders->merge($dayOrders);
                        $monthTotalOrders += $dayOrders->count();
                        $monthTotalAmount += $dayOrders->sum('total_amount');
                    }
                    $monthStart->addDay();
                }

                $calendarData[$monthKey] = [
                    'date' => $current->format('Y-m-01'), // First day of month
                    'month_name' => $current->format('F Y'),
                    'orders' => $monthOrders->values()->toArray(),
                    'total_orders' => $monthTotalOrders,
                    'total_amount' => $monthTotalAmount,
                ];

                $current->addMonth();
            }
        } else {
            // For day/week/month views, show individual days
            $calendarData = [];
            $current = $start->copy();

            while ($current <= $end) {
                $dateKey = $current->format('Y-m-d');
                $calendarData[$dateKey] = [
                    'date' => $current->format('Y-m-d'),
                    'day_name' => $current->format('l'),
                    'orders' => $orders->get($dateKey, collect())->toArray(),
                    'total_orders' => $orders->get($dateKey, collect())->count(),
                    'total_amount' => $orders->get($dateKey, collect())->sum('total_amount'),
                ];
                $current->addDay();
            }
        }

        return view('owner.pickup-calendar', compact('calendarData', 'period', 'start', 'end'));
    }
}
