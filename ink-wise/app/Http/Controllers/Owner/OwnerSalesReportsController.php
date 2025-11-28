<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class OwnerSalesReportsController extends Controller
{
    public function index(Request $request)
    {
        $rangeKey = Str::lower((string) $request->query('range', 'monthly'));
        [$start, $end, $rangeLabel, $normalizedRange] = $this->resolveRange($rangeKey);

        $orders = $this->queryCompletedOrders($start, $end);
        $metrics = $this->calculateMetrics($orders);
        $chartSeries = $this->buildChartSeries($orders, $normalizedRange, $start, $end);
        $recentSalesRows = $this->buildRecentSalesRows();

        $summaryCards = [
            [
                'label' => 'Total Sales',
                'chip' => ['text' => 'Revenue', 'accent' => true],
                'icon' => 'revenue',
                'value' => $this->formatCurrency($metrics['total_sales']),
                'meta' => $rangeLabel,
            ],
            [
                'label' => 'Orders Fulfilled',
                'chip' => ['text' => 'Orders', 'accent' => true],
                'icon' => 'orders',
                'value' => number_format($metrics['orders_fulfilled']),
                'meta' => 'Completed orders • ' . $rangeLabel,
            ],
            [
                'label' => 'Average Order Value',
                'chip' => ['text' => 'AOV', 'accent' => true],
                'icon' => 'average-order',
                'value' => $this->formatCurrency($metrics['average_order_value']),
                'meta' => 'Per completed order',
            ],
            [
                'label' => 'Profit',
                'chip' => ['text' => 'Profit', 'accent' => true],
                'icon' => 'profit',
                'value' => $this->formatCurrency($metrics['profit']),
                'meta' => 'After material costs • ' . $rangeLabel,
            ],
        ];

        return view('owner.reports.sales', [
            'summaryCards' => $summaryCards,
            'charts' => [
                [
                    'id' => 'salesChart',
                    'title' => 'Sales Overview',
                    'series' => $chartSeries,
                ],
            ],
            'activeRange' => $normalizedRange,
            'tableConfig' => [
                'headers' => ['Order ID', 'Customer', 'Items', 'Qty', 'Total (PHP)', 'Date'],
                'rows' => $recentSalesRows,
                'emptyText' => 'No recent sales found.',
                'showEmpty' => true,
            ],
        ]);
    }

    protected function queryCompletedOrders(?Carbon $start, ?Carbon $end): Collection
    {
        $query = Order::query()
            ->with([
                'items.paperStockSelection.paperStock.material',
                'items.addons.productAddon.material',
            ])
            ->where('status', 'completed');

        if ($start && $end) {
            $query->whereBetween(DB::raw('COALESCE(order_date, created_at)'), [$start, $end]);
        }

        return $query->get();
    }

    protected function calculateMetrics(Collection $orders): array
    {
        $orderCount = $orders->count();
        $totalSales = (float) $orders->sum(fn (Order $order) => (float) $order->total_amount);

        $materialCost = $orders->sum(function (Order $order) {
            return $this->estimateMaterialCost($order);
        });

        $profit = $totalSales - $materialCost;

        return [
            'total_sales' => round($totalSales, 2),
            'orders_fulfilled' => $orderCount,
            'average_order_value' => $orderCount > 0 ? round($totalSales / $orderCount, 2) : 0.0,
            'profit' => round($profit, 2),
        ];
    }

    protected function buildRecentSalesRows(int $limit = 10): array
    {
        $timezone = $this->timezone();

        $orders = Order::query()
            ->with([
                'items',
                'customer',
                'customerOrder',
            ])
            ->where('status', 'completed')
            ->orderByDesc(DB::raw('COALESCE(order_date, created_at)'))
            ->limit($limit)
            ->get();

        return $orders->map(function (Order $order) use ($timezone) {
            $moment = $this->resolveOrderMoment($order, $timezone);
            [$itemSummary, $quantity] = $this->summarizeOrderItems($order);

            return [
                'date_iso' => $moment?->toDateString(),
                'date_index' => 5,
                'columns' => [
                    ['text' => $this->formatOrderNumber($order), 'emphasis' => true],
                    ['text' => $this->resolveCustomerName($order)],
                    ['text' => $itemSummary, 'muted' => true],
                    ['text' => $quantity > 0 ? number_format($quantity) : '—', 'numeric' => true],
                    ['text' => number_format((float) $order->total_amount, 2), 'numeric' => true],
                    ['text' => $moment ? $moment->format('M d, Y') : '—', 'muted' => true],
                ],
            ];
        })->toArray();
    }

    protected function buildChartSeries(Collection $orders, string $range, ?Carbon $start, ?Carbon $end): array
    {
        $timezone = $this->timezone();
        $now = Carbon::now($timezone);

        $moments = $orders
            ->map(fn (Order $order) => $this->resolveOrderMoment($order, $timezone))
            ->filter();

        $effectiveStart = $start?->copy();
        $effectiveEnd = $end?->copy();

        if (!$effectiveStart && $moments->isNotEmpty()) {
            $effectiveStart = $moments->min()?->copy();
        }

        if (!$effectiveEnd && $moments->isNotEmpty()) {
            $effectiveEnd = $moments->max()?->copy();
        }

        if (!$effectiveStart) {
            $effectiveStart = match ($range) {
                'daily' => $now->copy()->startOfDay(),
                'weekly' => $now->copy()->subDays(6)->startOfDay(),
                'monthly' => $now->copy()->subDays(29)->startOfDay(),
                'yearly' => $now->copy()->startOfYear(),
                default => $now->copy()->subMonths(5)->startOfMonth(),
            };
        }

        if (!$effectiveEnd) {
            $effectiveEnd = match ($range) {
                'daily' => $effectiveStart->copy()->endOfDay(),
                'weekly', 'monthly' => $now->copy()->endOfDay(),
                'yearly' => $now->copy()->endOfDay(),
                default => $now->copy()->endOfMonth(),
            };
        }

        // Normalize ordering of start/end
        if ($effectiveEnd->lessThan($effectiveStart)) {
            [$effectiveStart, $effectiveEnd] = [$effectiveEnd->copy(), $effectiveStart->copy()];
        }

        $labels = [];
        $buckets = [];

        switch ($range) {
            case 'daily':
                $labels = array_map(fn ($hour) => sprintf('%02d:00', $hour), range(0, 23));
                $buckets = array_fill(0, 24, 0.0);

                foreach ($orders as $order) {
                    $moment = $this->resolveOrderMoment($order, $timezone);
                    if (!$moment || !$moment->isSameDay($effectiveStart)) {
                        continue;
                    }

                    $hour = (int) $moment->format('G');
                    $buckets[$hour] += (float) $order->total_amount;
                }

                break;

            case 'weekly':
            case 'monthly':
                $period = CarbonPeriod::create($effectiveStart->copy()->startOfDay(), '1 day', $effectiveEnd->copy()->startOfDay());
                foreach ($period as $date) {
                    $key = $date->format('Y-m-d');
                    $labels[] = $date->format('M j');
                    $buckets[$key] = 0.0;
                }

                foreach ($orders as $order) {
                    $moment = $this->resolveOrderMoment($order, $timezone);
                    if (!$moment) {
                        continue;
                    }

                    if ($moment->lt($effectiveStart) || $moment->gt($effectiveEnd)) {
                        continue;
                    }

                    $key = $moment->copy()->startOfDay()->format('Y-m-d');
                    if (array_key_exists($key, $buckets)) {
                        $buckets[$key] += (float) $order->total_amount;
                    }
                }

                break;

            case 'yearly':
            case 'all':
            default:
                $startMonth = $effectiveStart->copy()->startOfMonth();
                $endMonth = $effectiveEnd->copy()->endOfMonth();
                $period = CarbonPeriod::create($startMonth, '1 month', $endMonth);

                foreach ($period as $date) {
                    $key = $date->format('Y-m');
                    $labels[] = $date->format('M Y');
                    $buckets[$key] = 0.0;
                }

                foreach ($orders as $order) {
                    $moment = $this->resolveOrderMoment($order, $timezone);
                    if (!$moment) {
                        continue;
                    }

                    if ($moment->lt($startMonth) || $moment->gt($endMonth)) {
                        continue;
                    }

                    $key = $moment->copy()->startOfMonth()->format('Y-m');
                    if (array_key_exists($key, $buckets)) {
                        $buckets[$key] += (float) $order->total_amount;
                    }
                }

                break;
        }

        $values = array_values(array_map(fn ($value) => round((float) $value, 2), $buckets));

        if (empty($labels)) {
            $labels = ['No data'];
            $values = [0.0];
        }

        return [
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Sales',
                'data' => $values,
                'borderColor' => '#2b6cb0',
                'backgroundColor' => 'rgba(43,108,176,0.08)',
                'borderWidth' => 2.2,
                'pointRadius' => 3.5,
                'pointBackgroundColor' => '#2b6cb0',
                'tension' => 0.25,
                'fill' => true,
            ]],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];
    }

    protected function estimateMaterialCost(Order $order): float
    {
        $cost = 0.0;

        foreach ($order->items as $item) {
            $quantity = max((int) $item->quantity, 1);

            $paperStockMaterialCost = data_get($item, 'paperStockSelection.paperStock.material.unit_cost');
            if ($paperStockMaterialCost !== null) {
                $cost += (float) $paperStockMaterialCost * $quantity;
            }

            foreach ($item->addons as $addon) {
                $addonMaterialCost = data_get($addon, 'productAddon.material.unit_cost');
                if ($addonMaterialCost !== null) {
                    $addonQty = max((int) ($addon->quantity ?? 1), 1);
                    $cost += (float) $addonMaterialCost * $addonQty;
                }
            }
        }

        return round($cost, 2);
    }

    protected function resolveRange(string $rangeKey): array
    {
        $now = Carbon::now($this->timezone());
        $start = null;
        $end = null;
        $label = 'All time';
        $normalized = match ($rangeKey) {
            'daily', 'today' => 'daily',
            'weekly', 'last7', '7d' => 'weekly',
            'monthly', 'last30', '30d' => 'monthly',
            'yearly', 'ytd', 'thisyear' => 'yearly',
            default => 'all',
        };

        switch ($normalized) {
            case 'daily':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Today';
                break;
            case 'weekly':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = sprintf('Last 7 days (%s - %s)', $start->format('M j'), $end->format('M j'));
                break;
            case 'monthly':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = sprintf('Last 30 days (%s - %s)', $start->format('M j'), $end->format('M j'));
                break;
            case 'yearly':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfDay();
                $label = sprintf('Year to date (%s - %s)', $start->format('M j'), $end->format('M j'));
                break;
            default:
                $normalized = 'all';
                $label = 'All time';
                break;
        }

        return [$start, $end, $label, $normalized];
    }

    protected function resolveOrderMoment(Order $order, string $timezone): ?Carbon
    {
        $moment = $order->order_date ?? $order->created_at;

        if (!$moment) {
            return null;
        }

        if (!$moment instanceof Carbon) {
            $moment = Carbon::parse($moment, $timezone);
        }

        return $moment->copy()->timezone($timezone);
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

        $parts = collect([
            optional($customer)->first_name,
            optional($customer)->middle_name,
            optional($customer)->last_name,
        ])->filter()->implode(' ');

        if ($parts !== '') {
            return $parts;
        }

        if ($customerOrder && !empty($customerOrder->name)) {
            return $customerOrder->name;
        }

        if ($customer && !empty($customer->email)) {
            return $customer->email;
        }

        if ($customerOrder && !empty($customerOrder->email)) {
            return $customerOrder->email;
        }

        return 'Guest customer';
    }

    protected function summarizeOrderItems(Order $order): array
    {
        $items = $order->items ?? collect();
        $names = $items->pluck('product_name')->filter()->values();
        $quantity = (int) $items->sum('quantity');

        if ($names->isEmpty()) {
            return ['—', $quantity];
        }

        $primary = $names->take(2)->implode(', ');
        $extraCount = max($names->count() - 2, 0);

        if ($extraCount > 0) {
            $primary .= ' +' . $extraCount . ' more';
        }

        return [$primary, $quantity];
    }

    protected function formatCurrency(float $value): string
    {
        $formatted = number_format($value, 2);

        return '₱' . $formatted;
    }

    protected function timezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}
