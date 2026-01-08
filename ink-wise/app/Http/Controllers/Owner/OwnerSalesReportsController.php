<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Reports\SalesMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OwnerSalesReportsController extends Controller
{
    protected SalesMetricsService $salesMetrics;

    public function __construct(SalesMetricsService $salesMetrics)
    {
        $this->salesMetrics = $salesMetrics;
    }

    public function index(Request $request)
    {
        $rangeKey = Str::lower((string) $request->query('range', 'all'));
        [$customStart, $customEnd] = $this->resolveCustomDateRange($request);

        if ($customStart && $customEnd && $customEnd->lessThan($customStart)) {
            [$customStart, $customEnd] = [$customEnd->copy(), $customStart->copy()];
        }

        if ($customStart || $customEnd) {
            $start = $customStart?->copy();
            $end = $customEnd?->copy();
            $normalizedRange = 'custom';
            $rangeLabel = $this->formatCustomRangeLabel($start, $end);
        } else {
            [$start, $end, $rangeLabel, $normalizedRange] = $this->resolveRange($rangeKey);
        }

        $orderStatusFilter = Str::lower((string) $request->query('order_status', 'completed'));
        $orderStatusFilter = match ($orderStatusFilter) {
            'all' => 'all',
            'not_completed', 'incomplete', 'pending' => 'not_completed',
            default => 'completed',
        };

        $paymentStatusFilter = Str::lower((string) $request->query('payment_status', 'all'));
        $paymentStatusFilter = match ($paymentStatusFilter) {
            'full', 'fully-paid', 'paid' => 'full',
            'half', 'partial', 'partially-paid', 'partially_paid' => 'half',
            'unpaid', 'none' => 'unpaid',
            default => 'all',
        };

        $reportContext = $this->salesMetrics->compute($start, $end, $paymentStatusFilter, $orderStatusFilter);

        $salesSummaryTotals = $reportContext['salesSummaryTotals'];
        $paymentSummary = $reportContext['paymentSummary'];
        $salesIntervals = $reportContext['salesIntervals'];
        $defaultSalesInterval = $reportContext['defaultSalesInterval'];
        $salesSummaryLabel = $reportContext['salesSummaryLabel'];
        $salesCollection = $reportContext['sales'];

        $chartSeries = $this->buildChartSeriesFromIntervals($salesIntervals, $defaultSalesInterval);
        $recentSalesRows = $this->buildRecentSalesRowsFromCollection($salesCollection);

        $rangeChip = match ($normalizedRange) {
            'daily' => 'Today',
            'weekly' => 'Last 7 days',
            'monthly' => 'Last 30 days',
            'yearly' => 'This year',
            'custom' => $this->formatCustomChipLabel($start, $end),
            default => 'All time',
        };

        $summaryCards = [
            [
                'label' => 'Orders',
                'chip' => ['text' => $rangeChip, 'accent' => false],
                'icon' => 'orders',
                'value' => number_format($salesSummaryTotals['orders'] ?? 0),
                'metric' => 'orders-count',
                'meta' => 'Orders matching your filters.',
            ],
            [
                'label' => 'Fully Paid Revenue',
                'chip' => ['text' => 'Collected', 'accent' => true],
                'icon' => 'revenue',
                'value' => $this->formatCurrency($salesSummaryTotals['revenue'] ?? 0),
                'metric' => 'revenue-paid',
                'meta' => 'Revenue recognized from fully settled orders.',
            ],
            [
                'label' => 'Material Cost',
                'chip' => ['text' => 'Cost of goods', 'accent' => false],
                'icon' => 'inventory-total',
                'value' => $this->formatCurrency($salesSummaryTotals['materialCost'] ?? 0),
                'metric' => 'material-cost',
                'meta' => 'Materials consumed by fulfilled orders.',
            ],
            [
                'label' => 'Net Profit',
                'chip' => ['text' => 'Margin', 'accent' => true],
                'icon' => 'profit',
                'value' => $this->formatCurrency($salesSummaryTotals['profit'] ?? 0),
                'metric' => 'profit-total',
                'meta' => 'Profit after material costs.',
                'metaHtml' => sprintf('Profit after material costs. <span data-metric="profit-margin">%s margin</span>', $this->formatPercent($salesSummaryTotals['profitMargin'] ?? 0)),
            ],
            [
                'label' => 'Pending Balance',
                'chip' => ['text' => 'Receivables', 'accent' => false],
                'icon' => 'inventory-pending',
                'value' => $this->formatCurrency($salesSummaryTotals['pendingRevenue'] ?? 0),
                'metric' => 'pending-revenue',
                'meta' => 'Outstanding balances from partial payments.',
            ],
            [
                'label' => 'Avg. Order Value',
                'chip' => ['text' => 'Efficiency', 'accent' => false],
                'icon' => 'average-order',
                'value' => $this->formatCurrency($salesSummaryTotals['averageOrder'] ?? 0),
                'metric' => 'average-order',
                'meta' => 'Average revenue per completed order.',
            ],
        ];

        $paymentStatusQueryValue = match ($paymentStatusFilter) {
            'full' => 'full',
            'half' => 'partial',
            'unpaid' => 'unpaid',
            default => 'all',
        };

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
            'rangeLabel' => $rangeLabel,
            'tableConfig' => [
                'headers' => ['Order ID', 'Customer', 'Items', 'Qty', 'Payment Status', 'Total (PHP)', 'Profit (PHP)', 'Date'],
                'rows' => $recentSalesRows,
                'emptyText' => 'No recent sales found.',
                'showEmpty' => true,
            ],
            'salesSummaryTotals' => $salesSummaryTotals,
            'salesSummaryLabel' => $salesSummaryLabel,
            'paymentSummary' => $paymentSummary,
            'salesIntervals' => $salesIntervals,
            'rangeReload' => true,
            'orderStatusFilterEnabled' => true,
            'paymentStatusFilterEnabled' => true,
            'filters' => [
                'range' => $normalizedRange,
                'orderStatus' => $orderStatusFilter,
                'paymentStatus' => $paymentStatusQueryValue,
                'startDate' => $start?->format('Y-m-d'),
                'endDate' => $end?->format('Y-m-d'),
            ],
        ]);
    }

    protected function resolveCustomDateRange(Request $request): array
    {
        $startInput = trim((string) $request->query('start_date', ''));
        $endInput = trim((string) $request->query('end_date', ''));

        $startDate = null;
        $endDate = null;

        if ($startInput !== '') {
            try {
                $startDate = Carbon::parse($startInput)->startOfDay();
            } catch (\Throwable $e) {
                $startDate = null;
            }
        }

        if ($endInput !== '') {
            try {
                $endDate = Carbon::parse($endInput)->endOfDay();
            } catch (\Throwable $e) {
                $endDate = null;
            }
        }

        return [$startDate, $endDate];
    }

    protected function formatCustomRangeLabel(?Carbon $start, ?Carbon $end): string
    {
        if ($start && $end) {
            return sprintf('%s - %s', $start->format('M j, Y'), $end->format('M j, Y'));
        }

        if ($start) {
            return sprintf('From %s', $start->format('M j, Y'));
        }

        if ($end) {
            return sprintf('Up to %s', $end->format('M j, Y'));
        }

        return 'Custom range';
    }

    protected function formatCustomChipLabel(?Carbon $start, ?Carbon $end): string
    {
        if ($start && $end) {
            return $start->format('M j') . ' - ' . $end->format('M j');
        }

        if ($start) {
            return 'From ' . $start->format('M j');
        }

        if ($end) {
            return 'Through ' . $end->format('M j');
        }

        return 'Custom range';
    }

    protected function buildChartSeriesFromIntervals(array $intervals, string $activeKey): array
    {
        $active = $intervals[$activeKey] ?? reset($intervals) ?? null;

        $labels = $active['labels'] ?? [];
        $totals = $active['totals'] ?? [];

        if (empty($labels)) {
            $labels = ['No data'];
            $totals = [0.0];
        }

        $values = array_map(fn ($value) => round((float) $value, 2), $totals);

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

    protected function buildRecentSalesRowsFromCollection(Collection $orders): array
    {
        $timezone = $this->timezone();

        return $orders
            ->sortByDesc(function (Order $order) {
                return $order->order_date_value ?? $order->order_date ?? $order->created_at;
            })
            ->take(100)
            ->map(function (Order $order) use ($timezone) {
                $moment = $this->resolveOrderMoment($order, $timezone);
                $itemsSummary = $order->items_list ?? '—';
                $quantity = (int) ($order->items_quantity ?? 0);
                $profit = (float) ($order->profit_value ?? 0);
                $totalAmount = (float) ($order->total_amount_value ?? 0);

                return [
                    'date_iso' => $moment?->toDateString(),
                    'date_index' => 7,
                    'columns' => [
                        ['text' => $this->formatOrderNumber($order), 'emphasis' => true],
                        ['text' => $order->customer_name ?? '-'],
                        ['text' => $itemsSummary, 'muted' => true],
                        ['text' => $quantity > 0 ? number_format($quantity) : '—', 'numeric' => true],
                        ['text' => $order->payment_status ?? '-', 'muted' => ($order->payment_status ?? '') !== 'Full Payment'],
                        ['text' => number_format($totalAmount, 2), 'numeric' => true],
                        ['text' => number_format($profit, 2), 'numeric' => true, 'class' => $profit < 0 ? 'text-danger' : null],
                        ['text' => $moment ? $moment->format('M d, Y') : '—', 'muted' => true],
                    ],
                ];
            })
            ->values()
            ->toArray();
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

    protected function formatCurrency(float $value): string
    {
        $formatted = number_format($value, 2);

        return '₱' . $formatted;
    }

    protected function formatPercent(float $value): string
    {
        $rounded = round($value, 1);
        $formatted = number_format($rounded, 1, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.') . '%';
    }

    protected function timezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}
