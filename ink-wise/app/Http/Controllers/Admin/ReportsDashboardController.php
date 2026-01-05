<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerFinalized;
use App\Models\Material;
use App\Models\Ink;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ReportsDashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->sales($request);
    }

    public function sales(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $paymentStatusFilter = $request->input('payment_status', 'all'); // all, full, half, unpaid

        $context = $this->buildReportContext($startDate, $endDate, $paymentStatusFilter);
        $context['filters'] = [
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
            'paymentStatus' => $paymentStatusFilter,
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

    private function buildReportContext(?Carbon $startDate = null, ?Carbon $endDate = null, string $paymentStatusFilter = 'all'): array
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

        $allInventoryItems = $materials->concat($inks);
        $materialLabels = $allInventoryItems->pluck('material_name');
        $materialStockLevels = $allInventoryItems->map(fn ($item) => $this->resolveStockLevel($item));
        $materialReorderLevels = $allInventoryItems->map(fn ($item) => $this->resolveReorderLevel($item));
        $inventoryStats = $this->buildInventorySummary($allInventoryItems);

        [$specialMaterialStats, $specialMaterialSelections] = $this->buildSpecialMaterialReport($startDate, $endDate);

        $salesQuery = Order::query()
            ->with([
                'customer:customer_id,first_name,last_name',
                'customerOrder:id,name',
                'items:id,order_id,product_name,quantity',
                'items.paperStockSelection.paperStock.material:material_id,unit_cost',
                'items.addons.productAddon.material:material_id,unit_cost',
                'payments:order_id,amount,status',
            ])
            ->where('status', 'completed')
            ->latest('order_date');

        $salesQuery = $this->applyOrderDateFilter($salesQuery, $startDate, $endDate);

        $sales = $salesQuery
            ->take(100)
            ->get()
            ->map(function (Order $order) use ($paymentStatusFilter) {
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

                // Determine payment status
                $totalPaid = $order->totalPaid();
                $balanceDue = $order->balanceDue();
                if ($balanceDue == 0) {
                    $order->payment_status = 'Full Payment';
                } elseif ($totalPaid > 0) {
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
                    default => true,
                };
            })
            ->take(100); // Limit after filtering

        $analyticsQuery = Order::query()
            ->select(['id', 'total_amount', 'order_date', 'created_at'])
            ->with(['payments:order_id,amount,status'])
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

        $paymentSummary = $this->buildPaymentSummary($analyticsOrders);

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
            'paymentSummary',
            'specialMaterialStats',
            'specialMaterialSelections'
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
        return (int) (optional($item->inventory)->stock_level ?? $item->stock_qty ?? 0);
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

    private function buildSpecialMaterialReport(?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = CustomerFinalized::query()
            ->with([
                'customer:customer_id,first_name,last_name,email',
                'product:id,name',
                'template:id,name',
                'order' => function ($relation) {
                    $relation->select([
                        'id',
                        'order_number',
                        'customer_id',
                        'customer_order_id',
                        'date_needed',
                        'status',
                        'order_date',
                        'created_at',
                        'total_amount',
                    ])->with([
                        'customer:customer_id,first_name,last_name,email',
                        'customerOrder:id,customer_id,name,email,phone',
                        'customerOrder.customer:customer_id,first_name,last_name,email',
                    ]);
                },
            ])
            ->orderByDesc('created_at');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate->copy()->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate->copy()->endOfDay());
        }

        $records = $query->get();

        $mapped = $records
            ->map(function (CustomerFinalized $record) {
                $paperStock = $this->normalizeToArray($record->paper_stock);
                $statusBundle = $this->resolveSpecialMaterialStatus($record, $paperStock);

                if (!$statusBundle) {
                    return null;
                }

                [$statusKey, $statusLabel] = $statusBundle;

                $order = $record->order;
                $primaryCustomer = $record->customer
                    ?? $order?->customer
                    ?? $order?->customerOrder?->customer;

                $customerName = $primaryCustomer
                    ? $this->composeName(
                        $primaryCustomer->first_name ?? null,
                        $primaryCustomer->last_name ?? null,
                        $primaryCustomer->name ?? null
                    )
                    : null;

                if (!$customerName && $order && $order->customerOrder) {
                    $fallbackCustomer = $order->customerOrder->customer ?? null;
                    $customerName = $this->composeName(
                        $fallbackCustomer?->first_name ?? null,
                        $fallbackCustomer?->last_name ?? null,
                        $order->customerOrder->name ?? null
                    );
                }

                $customerEmail = $primaryCustomer?->email
                    ?? $order?->customerOrder?->email
                    ?? null;

                $paperStatusNormalized = $this->normalizeStatusString($paperStock['status'] ?? null);
                $paperStatusLabel = $paperStatusNormalized
                    ? Str::headline(str_replace('_', ' ', $paperStatusNormalized))
                    : null;

                $paperStockName = $paperStock['name']
                    ?? $paperStock['label']
                    ?? $paperStock['material']
                    ?? $paperStock['paper']
                    ?? null;

                $notes = $this->resolveNotes([
                    $paperStock['note'] ?? null,
                    $paperStock['notes'] ?? null,
                    $paperStock['message'] ?? null,
                    Arr::get($record->design, 'metadata.note'),
                    Arr::get($record->design, 'metadata.notes'),
                    Arr::get($record->design, 'notes'),
                ]);

                return [
                    'id' => $record->id,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'order_number' => $order?->order_number
                        ?? ($record->order_id ? '#' . $record->order_id : '—'),
                    'customer_name' => $customerName ?: '—',
                    'customer_email' => $customerEmail,
                    'product_name' => $record->product?->name
                        ?? $record->template?->name
                        ?? 'Custom Item',
                    'paper_stock_name' => $paperStockName ?: 'Unspecified',
                    'paper_stock_status_label' => $paperStatusLabel,
                    'quantity' => (int) ($record->quantity ?? 0),
                    'requested_at' => $this->formatReportDate($record->created_at),
                    'needed_date' => $this->formatReportDate($order?->date_needed ?? $record->estimated_date),
                    'notes' => $notes ?: null,
                    'created_at' => $record->created_at instanceof Carbon
                        ? $record->created_at->copy()
                        : ($record->created_at ? Carbon::parse($record->created_at) : Carbon::now()),
                ];
            })
            ->filter();

        $sorted = $mapped->sort(function (array $a, array $b) {
            $priorityA = $a['status_key'] === 'pre_order' ? 0 : 1;
            $priorityB = $b['status_key'] === 'pre_order' ? 0 : 1;

            if ($priorityA !== $priorityB) {
                return $priorityA <=> $priorityB;
            }

            $timestampA = $a['created_at'] instanceof Carbon ? $a['created_at']->timestamp : 0;
            $timestampB = $b['created_at'] instanceof Carbon ? $b['created_at']->timestamp : 0;

            return $timestampB <=> $timestampA;
        })->values();

        $selections = $sorted->map(function (array $selection) {
            $selection['notes'] = $selection['notes'] ?? '—';

            return Arr::except($selection, ['created_at']);
        });

        $stats = [
            'total' => $selections->count(),
            'pre_order' => $selections->where('status_key', 'pre_order')->count(),
            'out_of_stock' => $selections->where('status_key', 'out_of_stock')->count(),
            'total_quantity' => (int) $selections->sum('quantity'),
        ];

        return [$stats, $selections];
    }

    private function normalizeToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Collection) {
            return $value->toArray();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return (array) $value->toArray();
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    private function resolveSpecialMaterialStatus(CustomerFinalized $record, array $paperStock): ?array
    {
        $candidates = collect([
            $record->pre_order_status ?? null,
            Arr::get($record->design, 'metadata.pre_order_status'),
            Arr::get($paperStock, 'status'),
            Arr::get($paperStock, 'state'),
            Arr::get($paperStock, 'availability'),
            Arr::get($paperStock, 'availability_status'),
        ])->filter()->map(fn ($value) => $this->normalizeStatusString($value));

        $isPreOrder = $candidates->contains(function ($value) {
            return $value && in_array($value, [
                'pre_order',
                'preorder',
                'pre-order',
                'pre_ordered',
                'preorder_requested',
                'backorder',
                'back_order',
            ], true);
        }) || $this->isTruthyFlag(Arr::get($paperStock, 'preorder'))
            || $this->isTruthyFlag(Arr::get($paperStock, 'is_preorder'))
            || $this->isTruthyFlag(Arr::get($paperStock, 'requires_preorder'))
            || $this->isTruthyFlag(Arr::get($paperStock, 'pre_order'));

        $isOutOfStock = $candidates->contains(function ($value) {
            return $value && in_array($value, [
                'out_of_stock',
                'out-of-stock',
                'out_stock',
                'outstock',
                'sold_out',
                'sold-out',
                'unavailable',
                'no_stock',
                'not_available',
                'stockout',
            ], true);
        });

        if (!$isOutOfStock) {
            $quantities = collect([
                Arr::get($paperStock, 'available'),
                Arr::get($paperStock, 'quantity'),
                Arr::get($paperStock, 'stock'),
                Arr::get($paperStock, 'remaining'),
                Arr::get($paperStock, 'quantity_available'),
            ])->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (int) $value);

            if ($quantities->isNotEmpty() && $quantities->min() === 0) {
                $isOutOfStock = true;
            }
        }

        if (!$isPreOrder && !$isOutOfStock) {
            return null;
        }

        if ($isPreOrder) {
            return ['pre_order', 'Pre-order'];
        }

        return ['out_of_stock', 'Out of Stock'];
    }

    private function formatReportDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->format('M j, Y');
            }

            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isTruthyFlag($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value > 0;
        }

        if (is_string($value)) {
            $normalized = $this->normalizeStatusString($value);

            return $normalized !== null && in_array($normalized, [
                '1', 'true', 'yes', 'y', 'pending', 'pre_order', 'preorder', 'requested', 'on',
            ], true);
        }

        return false;
    }

    private function normalizeStatusString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            return null;
        }

        return str_replace([' ', '-'], '_', $normalized);
    }

    private function resolveNotes(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (is_array($candidate)) {
                $flattened = collect($candidate)
                    ->flatten()
                    ->filter(function ($entry) {
                        return is_scalar($entry) && trim((string) $entry) !== '';
                    })
                    ->implode(', ');

                if ($flattened !== '') {
                    return $flattened;
                }

                continue;
            }

            if (is_string($candidate)) {
                $trimmed = trim($candidate);

                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    private function composeName(?string $first, ?string $last, ?string $fallback = null): ?string
    {
        $full = trim(collect([$first, $last])->filter()->implode(' '));

        if ($full !== '') {
            return $full;
        }

        if ($fallback !== null) {
            $trimmed = trim($fallback);

            return $trimmed === '' ? null : $trimmed;
        }

        return null;
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

        // Only count fully paid orders for revenue
        $fullyPaidOrders = $filtered->filter(function (Order $order) {
            return $order->balanceDue() == 0;
        });

        $orderCount = $filtered->count();
        $revenue = (float) $fullyPaidOrders->sum('total_amount');
        $materialCost = (float) $fullyPaidOrders->sum('material_cost_value');
        $profit = $revenue - $materialCost;

        // Calculate pending revenue from partially paid orders
        $partiallyPaidOrders = $filtered->filter(function (Order $order) {
            $totalPaid = $order->totalPaid();
            return $totalPaid > 0 && $order->balanceDue() > 0;
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

    private function buildPaymentSummary(Collection $orders): array
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
}
