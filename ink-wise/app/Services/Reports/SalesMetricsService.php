<?php

namespace App\Services\Reports;

use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesMetricsService
{
    public function compute(?Carbon $startDate, ?Carbon $endDate, string $paymentStatusFilter = 'all', string $orderStatusFilter = 'completed'): array
    {
        $normalizedOrderStatus = $this->normalizeOrderStatusFilter($orderStatusFilter);

        $sales = $this->buildSalesCollection($startDate, $endDate, $paymentStatusFilter, $normalizedOrderStatus);

        $analyticsOrders = $this->buildAnalyticsCollection($startDate, $endDate, $normalizedOrderStatus);

        $salesIntervals = $this->buildSalesIntervals($analyticsOrders, $startDate, $endDate);
        $defaultSalesInterval = array_key_exists('weekly', $salesIntervals)
            ? 'weekly'
            : (array_key_first($salesIntervals) ?? 'daily');

        [$summaryStart, $summaryEnd] = $this->resolveSummaryWindow($analyticsOrders, $startDate, $endDate);
        $salesSummaryTotals = $this->buildOrdersSummary($analyticsOrders, $summaryStart, $summaryEnd);
        $salesSummaryTotals['estimatedSales'] = $this->calculateEstimatedSales($startDate, $endDate, $normalizedOrderStatus);
        $salesSummaryLabel = $this->formatRangeLabel($summaryStart, $summaryEnd);

        $paymentSummary = $this->buildPaymentSummary($analyticsOrders);

        return [
            'sales' => $sales,
            'salesIntervals' => $salesIntervals,
            'defaultSalesInterval' => $defaultSalesInterval,
            'salesSummaryTotals' => $salesSummaryTotals,
            'salesSummaryLabel' => $salesSummaryLabel,
            'paymentSummary' => $paymentSummary,
        ];
    }

    protected function buildSalesCollection(?Carbon $startDate, ?Carbon $endDate, string $paymentStatusFilter, string $orderStatusFilter): Collection
    {
        $query = Order::query()
            ->with([
                'customer:customer_id,first_name,last_name',
                'customerOrder:id,name',
                'items:id,order_id,product_name,quantity',
                'items.paperStockSelection.paperStock.material:material_id,unit_cost',
                'items.addons.productSize.material:material_id,unit_cost',
                'payments:order_id,amount,status',
            ])
            ->latest('order_date');

        $query = $this->applyOrderStatusFilter($query, $orderStatusFilter);

        $query = $this->applyOrderDateFilter($query, $startDate, $endDate);

        $orders = $query
            ->take(100)
            ->get()
            ->map(function (Order $order) {
                $customer = $order->customer;
                $fallbackName = optional($order->customerOrder)->name;

                $customerName = collect([
                    optional($customer)->first_name,
                    optional($customer)->last_name,
                ])->filter()->implode(' ');

                if (trim($customerName) === '' && $fallbackName) {
                    $customerName = $fallbackName;
                }

                $itemsList = $order->items
                    ->pluck('product_name')
                    ->filter()
                    ->values();

                $materialCost = 0.0;
                foreach ($order->items as $item) {
                    if ($item->paperStockSelection && $item->paperStockSelection->paperStock) {
                        $materialCost += ($item->paperStockSelection->paperStock->material->unit_cost ?? 0) * $item->quantity;
                    }

                    foreach ($item->addons as $addon) {
                        if ($addon->productAddon && $addon->productAddon->material) {
                            $materialCost += ($addon->productAddon->material->unit_cost ?? 0) * ($addon->quantity ?? 1);
                        }
                    }
                }

                $order->customer_name = $customerName !== '' ? $customerName : '-';
                $order->items_list = $itemsList->implode(', ');
                $order->items_quantity = (int) $order->items->sum('quantity');
                $order->total_amount_value = (float) $order->total_amount;
                $order->material_cost_value = round($materialCost, 2);
                $order->order_date_value = $order->order_date ?? $order->created_at;

                $totalPaid = (float) $order->totalPaid();
                $order->total_paid_value = $totalPaid;
                $order->profit_value = round($totalPaid - $order->material_cost_value, 2);

                $balanceDue = (float) $order->balanceDue();
                if ($balanceDue <= 0.01) {
                    $order->payment_status = 'Full Payment';
                } elseif ($totalPaid > 0.0) {
                    $order->payment_status = 'Half Payment';
                } else {
                    $order->payment_status = 'Unpaid';
                }

                return $order;
            })
            ->filter(function (Order $order) use ($paymentStatusFilter) {
                if ($paymentStatusFilter === 'all') {
                    return true;
                }

                return match ($paymentStatusFilter) {
                    'full' => $order->payment_status === 'Full Payment',
                    'half' => $order->payment_status === 'Half Payment',
                    'unpaid' => $order->payment_status === 'Unpaid',
                    'partial' => $order->payment_status === 'Half Payment',
                    default => true,
                };
            })
            ->take(100)
            ->values();

        return $orders;
    }

    protected function buildAnalyticsCollection(?Carbon $startDate, ?Carbon $endDate, string $orderStatusFilter): Collection
    {
        $query = Order::query()
            ->select(['id', 'total_amount', 'order_date', 'created_at'])
            ->with(['payments:order_id,amount,status'])
            ->whereRaw('COALESCE(order_date, created_at) >= ?', [
                Carbon::now()->copy()->subYears(5)->startOfYear(),
            ])
            ->orderByDesc('order_date')
            ->orderByDesc('created_at');

        $query = $this->applyOrderStatusFilter($query, $orderStatusFilter);

        $query = $this->applyOrderDateFilter($query, $startDate, $endDate);

        return $query->get();
    }

    protected function calculateEstimatedSales(?Carbon $startDate, ?Carbon $endDate, string $orderStatusFilter): float
    {
        $query = Order::query()
            ->whereNotIn('status', ['completed', 'cancelled']);

        $query = $this->applyOrderDateFilter($query, $startDate, $endDate);

        return (float) $query->sum('total_amount');
    }

    protected function normalizeOrderStatusFilter(string $value): string
    {
        return match (strtolower($value)) {
            'all' => 'all',
            'not_completed', 'incomplete', 'pending' => 'not_completed',
            default => 'completed',
        };
    }

    protected function applyOrderStatusFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'all' => $query->where('status', '!=', 'cancelled'),
            'not_completed' => $query->whereNotIn('status', ['completed', 'cancelled']),
            default => $query->where('status', 'completed'),
        };
    }

    protected function buildSalesIntervals(Collection $orders, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        $now = Carbon::now();

        $start = $rangeStart?->copy();
        $end = $rangeEnd?->copy();

        if ($start || $end) {
            $start ??= ($end?->copy()->subYears(5)->startOfYear()) ?? $now->copy()->subYears(5)->startOfYear();
            $end ??= $now->copy()->endOfDay();

            $configs = [
                'daily' => [
                    'start' => $start->copy()->startOfDay(),
                    'end' => $end->copy()->endOfDay(),
                    'step' => CarbonInterval::day(),
                    'format' => 'Y-m-d',
                    'label' => static fn (Carbon $date) => $date->format('M d'),
                ],
                'weekly' => [
                    'start' => $start->copy()->startOfWeek(),
                    'end' => $end->copy()->endOfWeek(),
                    'step' => CarbonInterval::week(),
                    'format' => 'o-W',
                    'label' => static fn (Carbon $date) => 'Wk ' . str_pad((string) $date->isoWeek(), 2, '0', STR_PAD_LEFT) . ' ' . $date->format('Y'),
                ],
                'monthly' => [
                    'start' => $start->copy()->startOfMonth(),
                    'end' => $end->copy()->endOfMonth(),
                    'step' => CarbonInterval::month(),
                    'format' => 'Y-m',
                    'label' => static fn (Carbon $date) => $date->format('M Y'),
                ],
                'yearly' => [
                    'start' => $start->copy()->startOfYear(),
                    'end' => $end->copy()->endOfYear(),
                    'step' => CarbonInterval::year(),
                    'format' => 'Y',
                    'label' => static fn (Carbon $date) => $date->format('Y'),
                ],
            ];
        } else {
            $configs = [
                'daily' => [
                    'start' => $now->copy()->subDays(13)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                    'step' => CarbonInterval::day(),
                    'format' => 'Y-m-d',
                    'label' => static fn (Carbon $date) => $date->format('M d'),
                ],
                'weekly' => [
                    'start' => $now->copy()->subWeeks(11)->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                    'step' => CarbonInterval::week(),
                    'format' => 'o-W',
                    'label' => static fn (Carbon $date) => 'Wk ' . str_pad((string) $date->isoWeek(), 2, '0', STR_PAD_LEFT) . ' ' . $date->format('Y'),
                ],
                'monthly' => [
                    'start' => $now->copy()->subMonths(11)->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                    'step' => CarbonInterval::month(),
                    'format' => 'Y-m',
                    'label' => static fn (Carbon $date) => $date->format('M Y'),
                ],
                'yearly' => [
                    'start' => $now->copy()->subYears(4)->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                    'step' => CarbonInterval::year(),
                    'format' => 'Y',
                    'label' => static fn (Carbon $date) => $date->format('Y'),
                ],
            ];
        }

        $intervals = [];

        foreach ($configs as $key => $config) {
            $intervals[$key] = $this->buildIntervalPayload(
                $orders,
                $config['start']->copy(),
                $config['end']->copy(),
                $config['step'],
                $config['format'],
                $config['label']
            );
        }

        return $intervals;
    }

    protected function buildIntervalPayload(
        Collection $orders,
        Carbon $start,
        Carbon $end,
        CarbonInterval $step,
        string $format,
        callable $labelFormatter
    ): array {
        $series = $this->buildIntervalSeries($orders, $start->copy(), $end->copy(), $step, $format, $labelFormatter);
        $summary = $this->buildOrdersSummary($orders, $start->copy(), $end->copy());

        return array_merge($series, [
            'summary' => $summary,
            'range_label' => $this->formatRangeLabel($start, $end),
        ]);
    }

    protected function buildIntervalSeries(
        Collection $orders,
        Carbon $start,
        Carbon $end,
        CarbonInterval $step,
        string $keyFormat,
        callable $labelFormatter
    ): array {
        $groupedTotals = $orders
            ->groupBy(function (Order $order) use ($keyFormat) {
                $moment = $order->order_date ?? $order->created_at;

                return $moment ? $moment->format($keyFormat) : null;
            })
            ->filter()
            ->map(fn (Collection $group) => (float) $group->sum('total_amount'))
            ->all();

        $period = new CarbonPeriod($start, $step, $end);
        $labels = [];
        $totals = [];

        foreach ($period as $datePoint) {
            $key = $datePoint->format($keyFormat);
            $labels[] = $labelFormatter($datePoint->copy());
            $totals[] = round($groupedTotals[$key] ?? 0, 2);
        }

        if (empty($labels)) {
            $labels[] = $labelFormatter($start->copy());
            $totals[] = 0.0;
        }

        return [
            'labels' => array_values($labels),
            'totals' => array_values($totals),
        ];
    }

    protected function buildOrdersSummary(Collection $orders, Carbon $start, Carbon $end): array
    {
        $filtered = $orders->filter(function (Order $order) use ($start, $end) {
            $moment = $order->order_date ?? $order->created_at;

            return $moment ? $moment->betweenIncluded($start, $end) : false;
        });

        $fullyPaidOrders = $filtered->filter(function (Order $order) {
            return $order->balanceDue() <= 0.01;
        });

        $orderCount = $filtered->count();
        $revenue = (float) $fullyPaidOrders->sum('total_amount');
        $realisedRevenue = (float) $filtered->sum(function (Order $order) {
            return (float) $order->totalPaid();
        });
        $materialCost = (float) $filtered->sum('material_cost_value');
        $profit = $realisedRevenue - $materialCost;

        $partiallyPaidOrders = $filtered->filter(function (Order $order) {
            $totalPaid = $order->totalPaid();
            return $totalPaid > 0 && $order->balanceDue() > 0.01;
        });
        $pendingRevenue = (float) $partiallyPaidOrders->sum(function (Order $order) {
            return $order->balanceDue();
        });

        return [
            'orders' => $orderCount,
            'revenue' => round($revenue, 2),
            'pendingRevenue' => round($pendingRevenue, 2),
            'materialCost' => round($materialCost, 2),
            'profit' => round($profit, 2),
            'averageOrder' => $orderCount > 0 ? round($revenue / $orderCount, 2) : 0.0,
            'profitMargin' => $realisedRevenue > 0 ? round(($profit / $realisedRevenue) * 100, 1) : 0.0,
        ];
    }

    protected function buildPaymentSummary(Collection $orders): array
    {
        $summary = [
            'totalPaid' => 0.0,
            'full' => ['count' => 0, 'amount' => 0.0],
            'half' => ['count' => 0, 'amount' => 0.0, 'balance' => 0.0],
        ];

        foreach ($orders as $order) {
            $paidAmount = (float) $order->totalPaid();
            $balanceDue = (float) $order->balanceDue();

            $summary['totalPaid'] += $paidAmount;

            if ($paidAmount <= 0 && $balanceDue <= 0) {
                continue;
            }

            if ($balanceDue <= 0 && $paidAmount > 0) {
                $summary['full']['count']++;
                $summary['full']['amount'] += $paidAmount;
                continue;
            }

            if ($paidAmount > 0 && $balanceDue > 0) {
                $summary['half']['count']++;
                $summary['half']['amount'] += $paidAmount;
                $summary['half']['balance'] += $balanceDue;
            }
        }

        $summary['totalPaid'] = round($summary['totalPaid'], 2);
        $summary['full']['amount'] = round($summary['full']['amount'], 2);
        $summary['half']['amount'] = round($summary['half']['amount'], 2);
        $summary['half']['balance'] = round($summary['half']['balance'], 2);

        return $summary;
    }

    protected function resolveSummaryWindow(Collection $orders, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $start = $startDate?->copy()->startOfDay();
        $end = $endDate?->copy()->endOfDay();

        $moments = $orders
            ->map(function (Order $order) {
                return $order->order_date ?? $order->created_at;
            })
            ->filter();

        if (!$start) {
            $firstMoment = $moments->min();
            $start = $firstMoment ? $firstMoment->copy()->startOfDay() : Carbon::now()->copy()->subDays(13)->startOfDay();
        }

        if (!$end) {
            $lastMoment = $moments->max();
            $end = $lastMoment ? $lastMoment->copy()->endOfDay() : Carbon::now()->copy()->endOfDay();
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [$start, $end];
    }

    protected function applyOrderDateFilter($query, ?Carbon $startDate, ?Carbon $endDate)
    {
        if (!$startDate && !$endDate) {
            return $query;
        }

        $expression = 'COALESCE(order_date, created_at)';

        if ($startDate) {
            $query->whereRaw("{$expression} >= ?", [$startDate->copy()->toDateTimeString()]);
        }

        if ($endDate) {
            $query->whereRaw("{$expression} <= ?", [$endDate->copy()->toDateTimeString()]);
        }

        return $query;
    }

    protected function formatRangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('M j, Y');
        }

        return sprintf('%s - %s', $start->format('M j, Y'), $end->format('M j, Y'));
    }
}
