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

    protected function queryOrders(array $filters): Collection
    {
        $query = Order::query()
            ->with(['customer', 'customerOrder', 'items.product'])
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

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
            'pending' => ['pending', 'in_production', 'processing', 'to_receive'],
            'confirmed' => ['confirmed', 'completed'],
            default => [$status],
        };
    }

    protected function buildSummaryCounts(): array
    {
        $row = Order::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(COALESCE(status, \'\')) IN (\'confirmed\', \'completed\') THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN LOWER(COALESCE(status, \'\')) IN (\'pending\', \'in_production\', \'processing\', \'to_receive\') THEN 1 ELSE 0 END) as pending
        ')->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'confirmed' => (int) ($row->confirmed ?? 0),
            'pending' => (int) ($row->pending ?? 0),
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
        $status = trim($status);

        return $status !== '' ? Str::of($status)->replace('_', ' ')->headline() : 'Pending';
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

    protected function timezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}
