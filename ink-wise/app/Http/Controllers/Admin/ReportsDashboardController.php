<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsDashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->sales($request);
    }

    public function sales(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $context = $this->buildReportContext($startDate, $endDate);
        $context['filters'] = [
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ];

        return view('admin.reports.sales', $context);
    }

    public function inventory(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $context = $this->buildReportContext($startDate, $endDate);
        $context['filters'] = [
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ];

        return view('admin.reports.inventory', $context);
    }

    public function pickupCalendar(Request $request)
    {
        $period = $request->input('period', 'week'); // day, week, month, year

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

        $view = auth()->user()->role === 'staff' ? 'staff.reports.pickup-calendar' : 'admin.reports.pickup-calendar';

        return view($view, compact('calendarData', 'period', 'start', 'end'));
    }

    private function buildReportContext(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $materials = Material::with('inventory')
            ->with(['stockMovements' => function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('created_at', '<=', $endDate);
                }
                $query->orderBy('created_at');
            }])
            ->orderBy('material_name')
            ->get();

        $materialLabels = $materials->pluck('material_name');
        $materialStockLevels = $materials->map(fn (Material $material) => $this->resolveStockLevel($material));
        $materialReorderLevels = $materials->map(fn (Material $material) => $this->resolveReorderLevel($material));
        $inventoryStats = $this->buildInventorySummary($materials);

        $salesQuery = Order::query()
            ->with([
                'customer:customer_id,first_name,last_name',
                'customerOrder:id,name',
                'items:id,order_id,product_name,quantity',
                'items.paperStockSelection.paperStock.material:material_id,unit_cost',
                'items.addons.productAddon.material:material_id,unit_cost',
            ])
            ->where('status', 'completed')
            ->latest('order_date');

        $salesQuery = $this->applyOrderDateFilter($salesQuery, $startDate, $endDate);

        $sales = $salesQuery
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

                // Calculate material costs
                $materialCost = 0;
                foreach ($order->items as $item) {
                    // Cost from paper stock
                    if ($item->paperStockSelection && $item->paperStockSelection->paperStock) {
                        $paperStockCost = ($item->paperStockSelection->paperStock->material->unit_cost ?? 0) * $item->quantity;
                        $materialCost += $paperStockCost;
                    }

                    // Cost from addons
                    foreach ($item->addons as $addon) {
                        if ($addon->productAddon && $addon->productAddon->material) {
                            $addonCost = ($addon->productAddon->material->unit_cost ?? 0) * ($addon->quantity ?? 1);
                            $materialCost += $addonCost;
                        }
                    }
                }

                $order->customer_name = $customerName !== '' ? $customerName : '-';
                $order->items_list = $itemsList->implode(', ');
                $order->items_quantity = (int) $order->items->sum('quantity');
                $order->total_amount_value = (float) $order->total_amount;
                $order->material_cost_value = $materialCost;
                $order->profit_value = $order->total_amount_value - $materialCost;
                $order->order_date_value = $order->order_date ?? $order->created_at;

                return $order;
            });

        $analyticsQuery = Order::query()
            ->select(['id', 'total_amount', 'order_date', 'created_at'])
            ->where('status', 'completed')
            ->whereRaw("COALESCE(order_date, created_at) >= ?", [
                Carbon::now()->copy()->subYears(5)->startOfYear(),
            ]);

    $analyticsQuery = $this->applyOrderDateFilter($analyticsQuery, $startDate, $endDate);

        $analyticsOrders = $analyticsQuery->get();

        $salesIntervals = $this->buildSalesIntervals($analyticsOrders, $startDate, $endDate);

        $defaultSalesInterval = array_key_exists('weekly', $salesIntervals)
            ? 'weekly'
            : (array_key_first($salesIntervals) ?? 'daily');

        [$summaryStart, $summaryEnd] = $this->resolveSummaryWindow($analyticsOrders, $startDate, $endDate);
        $salesSummaryTotals = $this->buildOrdersSummary($analyticsOrders, $summaryStart, $summaryEnd);
        $salesSummaryLabel = $this->formatRangeLabel($summaryStart, $summaryEnd);

        // Calculate estimated sales from incomplete orders (all non-completed orders)
        $estimatedSalesQuery = Order::query()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled');
        
        $estimatedSalesQuery = $this->applyOrderDateFilter($estimatedSalesQuery, $startDate, $endDate);
        $estimatedSales = (float) $estimatedSalesQuery->sum('total_amount');
        
        $salesSummaryTotals['estimatedSales'] = round($estimatedSales, 2);

        return compact(
            'materials',
            'materialLabels',
            'materialStockLevels',
            'materialReorderLevels',
            'inventoryStats',
            'sales',
            'salesIntervals',
            'defaultSalesInterval',
            'salesSummaryTotals',
            'salesSummaryLabel'
        );
    }

    private function resolveDateRange(Request $request): array
    {
        $startInput = $request->input('start_date');
        $endInput = $request->input('end_date');

        try {
            $startDate = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
        } catch (\Throwable $e) {
            $startDate = null;
        }

        try {
            $endDate = $endInput ? Carbon::parse($endInput)->endOfDay() : null;
        } catch (\Throwable $e) {
            $endDate = null;
        }

        if ($startDate && $endDate && $endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy(), $startDate->copy()];
        }

        return [$startDate, $endDate];
    }

    private function applyOrderDateFilter($query, ?Carbon $startDate, ?Carbon $endDate)
    {
        if (!$startDate && !$endDate) {
            return $query;
        }

        $coalesceExpression = 'COALESCE(order_date, created_at)';

        if ($startDate) {
            $query->whereRaw("{$coalesceExpression} >= ?", [$startDate->copy()->toDateTimeString()]);
        }

        if ($endDate) {
            $query->whereRaw("{$coalesceExpression} <= ?", [$endDate->copy()->toDateTimeString()]);
        }

        return $query;
    }

    private function resolveStockLevel(Material $material): int
    {
        return (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
    }

    private function resolveReorderLevel(Material $material): int
    {
        return (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);
    }

    private function buildInventorySummary(Collection $materials): array
    {
        $lowStock = 0;
        $outStock = 0;
        $totalValue = 0;

        foreach ($materials as $material) {
            $stock = $this->resolveStockLevel($material);
            $reorder = $this->resolveReorderLevel($material);
            $unitCost = $material->unit_cost ?? 0;

            if ($stock <= 0) {
                $outStock++;
                continue;
            }

            if ($reorder > 0 && $stock <= $reorder) {
                $lowStock++;
            }

            // Calculate inventory value
            $totalValue += $stock * $unitCost;
        }

        return [
            'totalSkus' => $materials->count(),
            'lowStock' => $lowStock,
            'outStock' => $outStock,
            'totalStock' => $materials->sum(fn (Material $material) => $this->resolveStockLevel($material)),
            'totalValue' => $totalValue,
        ];
    }

    private function buildSalesIntervals(Collection $orders, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
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

    private function buildIntervalPayload(
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

    private function buildIntervalSeries(
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

    private function buildOrdersSummary(Collection $orders, Carbon $start, Carbon $end): array
    {
        $filtered = $orders->filter(function (Order $order) use ($start, $end) {
            $moment = $order->order_date ?? $order->created_at;

            return $moment ? $moment->betweenIncluded($start, $end) : false;
        });

        $orderCount = $filtered->count();
        $revenue = (float) $filtered->sum('total_amount');
        $materialCost = (float) $filtered->sum('material_cost_value');
        $profit = $revenue - $materialCost;

        return [
            'orders' => $orderCount,
            'revenue' => round($revenue, 2),
            'materialCost' => round($materialCost, 2),
            'profit' => round($profit, 2),
            'averageOrder' => $orderCount > 0 ? round($revenue / $orderCount, 2) : 0.0,
            'profitMargin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
        ];
    }

    private function formatRangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('M j, Y');
        }

        return sprintf('%s - %s', $start->format('M j, Y'), $end->format('M j, Y'));
    }

    private function resolveSummaryWindow(Collection $orders, ?Carbon $startDate, ?Carbon $endDate): array
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
}
