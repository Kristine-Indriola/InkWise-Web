<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Ink;
use App\Models\Order;
use App\Services\Reports\SalesMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\ArchivedSalesReport;

class ReportsDashboardController extends Controller
{
    protected SalesMetricsService $salesMetrics;

    public function __construct(SalesMetricsService $salesMetrics)
    {
        $this->salesMetrics = $salesMetrics;
    }

    public function index(Request $request)
    {
        return $this->sales($request);
    }

    public function sales(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        // If an interval filter is supplied, compute the date range for that interval
        // and override explicit start/end so the whole page (cards, chart, table)
        // uses the requested interval window.
        $interval = $request->input('interval');
        if (is_string($interval) && in_array($interval, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $now = Carbon::now();
            switch ($interval) {
                case 'daily':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case 'weekly':
                    $startDate = $now->copy()->startOfWeek();
                    $endDate = $now->copy()->endOfWeek();
                    break;
                case 'monthly':
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfMonth();
                    break;
                case 'yearly':
                    $startDate = $now->copy()->startOfYear();
                    $endDate = $now->copy()->endOfYear();
                    break;
            }
        }

        $paymentStatusFilter = Str::lower((string) $request->input('payment_status', 'all'));
        $paymentStatusFilter = match ($paymentStatusFilter) {
            'full', 'fully-paid', 'paid' => 'full',
            'half', 'partial', 'partially-paid', 'partially_paid' => 'half',
            'unpaid', 'none' => 'unpaid',
            default => 'all',
        };

        $orderStatusFilter = Str::lower((string) $request->input('order_status', 'completed'));
        $orderStatusFilter = match ($orderStatusFilter) {
            'all' => 'all',
            'not_completed', 'incomplete', 'pending' => 'not_completed',
            default => 'completed',
        };

        $context = $this->buildReportContext($startDate, $endDate, $paymentStatusFilter, $orderStatusFilter);

        // Allow explicit interval selector (daily, weekly, monthly, yearly)
        $requestedInterval = $request->input('interval');
        if ($requestedInterval && is_string($requestedInterval) && array_key_exists($requestedInterval, $context['salesIntervals'])) {
            $context['defaultSalesInterval'] = $requestedInterval;
        }

        $context['filters'] = [
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
            'interval' => $context['defaultSalesInterval'] ?? null,
        ];

        return view('admin.reports.sales', $context);
    }

    public function archive(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'in:daily,weekly,monthly,yearly'],
        ]);

        $period = $data['period'];
        $now = Carbon::now();

        switch ($period) {
            case 'daily':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'weekly':
                $start = $now->copy()->startOfWeek()->subWeek();
                $end = $start->copy()->endOfWeek();
                break;
            case 'monthly':
                $start = $now->copy()->startOfMonth()->subMonth();
                $end = $start->copy()->endOfMonth();
                break;
            case 'yearly':
                $start = $now->copy()->startOfYear()->subYear();
                $end = $start->copy()->endOfYear();
                break;
            default:
                return back()->with('error', 'Invalid archive period');
        }

        // Compute snapshot using existing service
        $snapshot = $this->salesMetrics->compute($start, $end);

        // Persist archive record
        $archive = ArchivedSalesReport::create([
            'period' => $period,
            'start_date' => $start->toDateTimeString(),
            'end_date' => $end->toDateTimeString(),
            'payload' => $snapshot,
            'archived_by' => optional(auth()->user())->id,
        ]);

        // Mark matching orders as archived so they no longer count in dashboards
        Order::whereRaw("COALESCE(order_date, created_at) >= ? AND COALESCE(order_date, created_at) <= ?", [
            $start->toDateTimeString(), $end->toDateTimeString()
        ])->where(function ($q) {
            $q->whereNull('archived')->orWhere('archived', false);
        })->update(['archived' => true]);

        return back()->with('success', 'Sales report archived for ' . ucfirst($period) . '.');
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

        $view = auth()->user()->role === 'staff' ? 'staff.reports.pickup-calendar' : 'admin.reports.pickup-calendar';

        return view($view, compact('calendarData', 'period', 'start', 'end'));
    }

    private function buildReportContext(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $paymentStatusFilter = 'all',
        string $orderStatusFilter = 'completed'
    ): array
    {
        $materials = Material::with('inventory')
            ->where(function ($query) {
                $query->whereNull('material_type')
                      ->orWhereRaw("LOWER(TRIM(COALESCE(material_type, ''))) != 'ink'");
            })
            ->when(Schema::hasColumn('materials', 'ink_color'), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('ink_color')
                      ->orWhereRaw("TRIM(COALESCE(ink_color, '')) = ''");
                });
            })
            ->when(Schema::hasColumn('materials', 'cost_per_ml'), function ($query) {
                $query->whereNull('cost_per_ml');
            })
            ->when(Schema::hasColumn('materials', 'stock_qty_ml'), function ($query) {
                $query->whereNull('stock_qty_ml');
            })
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

        $inks = Ink::with(['inventory', 'stockMovements' => function ($query) use ($startDate, $endDate) {
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

        $materials->each(function ($material) {
            $material->inventory_key = 'material-' . $material->material_id;
        });

        $inks->each(function ($ink) {
            $ink->inventory_key = 'ink-' . $ink->id;
        });

        $allInventoryItems = $materials->concat($inks)->unique('inventory_key')->values();
        $materialLabels = $allInventoryItems->pluck('material_name');
        $materialStockLevels = $allInventoryItems->map(fn ($item) => $this->resolveStockLevel($item));
        $materialReorderLevels = $allInventoryItems->map(fn ($item) => $this->resolveReorderLevel($item));
        $inventoryStats = $this->buildInventorySummary($allInventoryItems);

        $salesReport = $this->salesMetrics->compute($startDate, $endDate, $paymentStatusFilter, $orderStatusFilter);

        $sales = $salesReport['sales'];
        $salesIntervals = $salesReport['salesIntervals'];
        $defaultSalesInterval = $salesReport['defaultSalesInterval'];
        $salesSummaryTotals = $salesReport['salesSummaryTotals'];
        $salesSummaryLabel = $salesReport['salesSummaryLabel'];
        $paymentSummary = $salesReport['paymentSummary'];

        return compact(
            'materials',
            'inks',
            'materialLabels',
            'materialStockLevels',
            'materialReorderLevels',
            'inventoryStats',
            'sales',
            'salesIntervals',
            'defaultSalesInterval',
            'salesSummaryTotals',
            'salesSummaryLabel',
            'paymentSummary'
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

    private function resolveStockLevel($item): int
    {
        $stock = optional($item->inventory)->stock_level;
        if ($stock !== null) {
            return (int) $stock;
        }

        if (isset($item->stock_qty) && $item->stock_qty !== null) {
            return (int) $item->stock_qty;
        }

        if (isset($item->stock_qty_ml) && $item->stock_qty_ml !== null) {
            return (int) $item->stock_qty_ml;
        }

        return 0;
    }

    private function resolveReorderLevel($item): int
    {
        return (int) (optional($item->inventory)->reorder_level ?? $item->reorder_point ?? 0);
    }

    private function buildInventorySummary(Collection $items): array
    {
        $lowStock = 0;
        $outStock = 0;
        $totalValue = 0;

        foreach ($items as $item) {
            $stock = $this->resolveStockLevel($item);
            $reorder = $this->resolveReorderLevel($item);
            $unitCost = $item->unit_cost ?? $item->cost_per_ml ?? 0;

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
            'totalSkus' => $items->count(),
            'lowStock' => $lowStock,
            'outStock' => $outStock,
            'totalStock' => $items->sum(fn ($item) => $this->resolveStockLevel($item)),
            'totalValue' => $totalValue,
        ];
    }

}
