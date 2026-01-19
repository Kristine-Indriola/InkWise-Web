<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\OrderRating;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\ImageResolver;
use App\Services\OrderFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $materials = Material::with('inventory')->get();

        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();

        $currentWeekOrdersQuery = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereRaw('COALESCE(order_date, created_at) BETWEEN ? AND ?', [$weekStart, $now]);

        $previousWeekStart = $weekStart->copy()->subWeek();
        $previousWeekEnd = $weekStart->copy()->subSecond();
        $previousWeekOrdersQuery = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereRaw('COALESCE(order_date, created_at) BETWEEN ? AND ?', [$previousWeekStart, $previousWeekEnd]);

        $ordersThisWeek = (clone $currentWeekOrdersQuery)->count();
        $revenueThisWeek = (clone $currentWeekOrdersQuery)->sum('total_amount');
        $averageOrderValue = $ordersThisWeek > 0 ? round($revenueThisWeek / $ordersThisWeek, 2) : 0.0;

        $ordersLastWeek = (clone $previousWeekOrdersQuery)->count();
        $revenueLastWeek = (clone $previousWeekOrdersQuery)->sum('total_amount');

        $ordersWoW = $this->calculateDelta($ordersThisWeek, $ordersLastWeek);
        $revenueWoW = $this->calculateDelta((float) $revenueThisWeek, (float) $revenueLastWeek);

        $pendingOrders = Order::query()
            ->where('status', 'pending')
            ->where('archived', false)
            ->count();

        $newOrders = Order::query()
            ->where('status', 'draft')
            ->where('archived', false)
            ->count();

        $inventorySummary = $this->summariseInventory($materials);

        $dashboardMetrics = [
            'ordersThisWeek' => $ordersThisWeek,
            'revenueThisWeek' => round((float) $revenueThisWeek, 2),
            'averageOrderValue' => $averageOrderValue,
            'pendingOrders' => $pendingOrders,
            'newOrders' => $newOrders,
            'lowStock' => $inventorySummary['lowStock'],
            'outOfStock' => $inventorySummary['outStock'],
            'totalStockUnits' => $inventorySummary['totalStock'],
            'totalSkus' => $inventorySummary['totalSkus'],
            'ordersWoW' => $ordersWoW,
            'revenueWoW' => $revenueWoW,
            'inventoryRiskPercent' => $inventorySummary['riskPercent'],
            'stockCoverageDays' => $inventorySummary['coverageDays'],
        ];

        $popularDesign = $this->resolvePopularDesign();

        $overviewStats = $this->buildOverviewStats($inventorySummary);
        $statusOptions = $this->dashboardStatusOptions();
        $orderManagement = $this->buildOrderManagementSection($statusOptions);
        $salesPreview = $this->buildSalesPreviewSection();
        $inventoryMonitor = $this->buildInventoryMonitorSection($materials);
        $customerInsights = $this->buildCustomerInsightsSection();
        $accountControl = $this->buildAccountControlSection();
        $systemShortcuts = $this->buildSystemShortcuts();
        $recentActivityFeed = $this->buildRecentActivityFeed();
        $upcomingCalendar = $this->buildUpcomingCalendarSection();
        $materialAlerts = $this->buildMaterialAlerts($materials);
        $dashboardAnnouncements = $this->resolveDashboardAnnouncements();
        $customerReviewSnapshot = $this->buildCustomerReviewSnapshot();

        return view('admin.dashboard', [
            'materials' => $materials,
            'dashboardMetrics' => $dashboardMetrics,
            'popularDesign' => $popularDesign,
            'overviewStats' => $overviewStats,
            'statusOptions' => $statusOptions,
            'orderManagement' => $orderManagement,
            'salesPreview' => $salesPreview,
            'inventoryMonitor' => $inventoryMonitor,
            'customerInsights' => $customerInsights,
            'accountControl' => $accountControl,
            'systemShortcuts' => $systemShortcuts,
            'recentActivityFeed' => $recentActivityFeed,
            'upcomingCalendar' => $upcomingCalendar,
            'materialAlerts' => $materialAlerts,
            'dashboardAnnouncements' => $dashboardAnnouncements,
            'customerReviewSnapshot' => $customerReviewSnapshot,
        ]);
    }

    private function buildOverviewStats(array $inventorySummary): array
    {
        $totalOrders = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->count();

        $totalSales = (float) Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->sum('total_amount');

        $totalCustomers = Customer::query()->count();

        $activeStaff = Staff::query()
            ->notArchived()
            ->count();

        $activeUsers = User::query()
            ->where('status', 'active')
            ->count();

        $pendingOrders = Order::query()
            ->where('status', 'pending')
            ->where('archived', false)
            ->count();

        return [
            'totalOrders' => $totalOrders,
            'totalSales' => round($totalSales, 2),
            'pendingOrders' => $pendingOrders,
            'lowStock' => $inventorySummary['lowStock'],
            'outOfStock' => $inventorySummary['outStock'],
            'totalCustomers' => $totalCustomers,
            'activeStaff' => $activeStaff,
            'activeUsers' => $activeUsers,
        ];
    }

    private function buildOrderManagementSection(array $statusOptions): array
    {
        $orders = Order::query()
            ->with([
                'customer:customer_id,first_name,last_name',
                'customerOrder:id,name',
                'items:id,order_id,product_name,quantity,line_type',
                'activities' => fn ($query) => $query->latest()->limit(5),
            ])
            ->where('archived', false)
            ->latest('updated_at')
            ->take(6)
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

                $order->dashboard_customer_name = $customerName !== '' ? $customerName : 'Guest';
                $order->dashboard_items_list = $order->items
                    ->pluck('product_name')
                    ->filter()
                    ->values();
                $order->dashboard_total_amount = round((float) ($order->total_amount ?? 0), 2);
                $order->dashboard_updated_at = $order->updated_at;

                $metadataPayload = strtolower(json_encode($order->metadata ?? []) ?? '');
                $snapshotPayload = strtolower(json_encode($order->summary_snapshot ?? []) ?? '');
                $order->dashboard_has_custom_request = Str::contains($metadataPayload . ' ' . $snapshotPayload, ['custom', 'personal']);

                return $order;
            });

        $statusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('archived', false)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $paymentStatusCounts = Order::query()
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->where('archived', false)
            ->whereRaw("LOWER(COALESCE(payment_status, '')) != ?", ['pending'])
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status')
            ->all();

        return [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'paymentStatusCounts' => $paymentStatusCounts,
            'statusOptions' => $statusOptions,
        ];
    }

    private function buildSalesPreviewSection(): array
    {
        $now = Carbon::now();

        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $previousWeekEnd = $startOfWeek->copy()->subDay();
        $previousWeekStart = $previousWeekEnd->copy()->startOfWeek();

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $previousMonthEnd = $startOfMonth->copy()->subDay();
        $previousMonthStart = $previousMonthEnd->copy()->startOfMonth();

        $startOfYear = $now->copy()->startOfYear();
        $previousYtdEnd = $now->copy()->subYear();
        $previousYtdStart = $previousYtdEnd->copy()->startOfYear();

        $dailyDetail = $this->makeSalesPeriodDetail($today, $today, $yesterday, $yesterday, 'Today');
        $weeklyDetail = $this->makeSalesPeriodDetail($startOfWeek, $endOfWeek, $previousWeekStart, $previousWeekEnd, 'This Week');
        $monthlyDetail = $this->makeSalesPeriodDetail($startOfMonth, $endOfMonth, $previousMonthStart, $previousMonthEnd, 'This Month');

        $currentYtd = $this->summariseSalesWindow($startOfYear, $now);
        $previousYtd = $this->summariseSalesWindow($previousYtdStart, $previousYtdEnd);

        $yearToDate = [
            'current' => $currentYtd,
            'previous' => $previousYtd,
            'salesDelta' => $this->calculateDelta($currentYtd['sales'], $previousYtd['sales']),
            'ordersDelta' => $this->calculateDelta($currentYtd['orders'], $previousYtd['orders']),
        ];

        $trend = $this->buildSalesTrendDataset();

        $bestSelling = OrderItem::query()
            ->selectRaw('order_items.product_id, COALESCE(order_items.product_name, "Custom Design") as label, SUM(order_items.quantity) as quantity, COUNT(DISTINCT order_items.order_id) as orders_count, SUM(COALESCE(order_items.subtotal, order_items.quantity * order_items.unit_price, 0)) as total_revenue, MAX(products.image) as product_image')
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled')
                    ->where('archived', false);
            })
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('order_items.product_id', 'label')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $productIds = $bestSelling
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $productLookup = Product::query()
            ->with([
                'images',
                'template',
                'uploads' => fn ($query) => $query->orderBy('created_at')->limit(4),
            ])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $orderFlow = app(OrderFlowService::class);

        $bestSelling = $bestSelling->map(function (OrderItem $item) use ($productLookup, $orderFlow) {
            $product = $item->product_id ? $productLookup->get($item->product_id) : null;
            $imageUrl = null;

            if ($product) {
                $images = $orderFlow->resolveProductImages($product);
                $imageUrl = $images['front'] ?? null;
            }

            $item->setAttribute('image_url', $imageUrl ? $imageUrl : ImageResolver::url($item->product_image ?? null));

            return $item;
        });

        $recentTransactions = Payment::query()
            ->with([
                'order:id,order_number,status,payment_status,archived',
                'recordedBy:user_id,name',
            ])
            ->where('archived', false)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', '!=', 'pending')
                  ->where('archived', false);
            })
            ->orderByDesc(DB::raw('COALESCE(recorded_at, created_at)'))
            ->limit(6)
            ->get();

        $paymentMethodBreakdown = $this->buildPaymentMethodBreakdown($now);

        return [
            'daily' => $dailyDetail['sales'],
            'weekly' => $weeklyDetail['sales'],
            'monthly' => $monthlyDetail['sales'],
            'trend' => $trend,
            'bestSelling' => $bestSelling,
            'recentTransactions' => $recentTransactions,
            'periodDetails' => [
                'daily' => $dailyDetail,
                'weekly' => $weeklyDetail,
                'monthly' => $monthlyDetail,
            ],
            'yearToDate' => $yearToDate,
            'paymentMethodBreakdown' => $paymentMethodBreakdown,
        ];
    }

    private function summariseSalesWindow(Carbon $start, Carbon $end): array
    {
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $coalesceExpression = 'DATE(COALESCE(order_date, created_at))';
        $revenueStatuses = ['processing', 'in_production', 'confirmed', 'completed'];

        $query = Order::query()
            ->where('archived', false)
            ->whereIn('status', $revenueStatuses)
            ->whereRaw("{$coalesceExpression} BETWEEN ? AND ?", [$startDate->toDateString(), $endDate->toDateString()]);

        $orderCount = (clone $query)->count();
        $salesTotal = (clone $query)->sum('total_amount');

        if ($salesTotal <= 0.0 && $orderCount > 0) {
            $salesTotal = (clone $query)
                ->select(['id', 'total_amount', 'summary_snapshot', 'metadata'])
                ->get()
                ->sum(fn (Order $order) => $order->grandTotalAmount());
        }

        $average = $orderCount > 0 ? round($salesTotal / $orderCount, 2) : 0.0;

        return [
            'sales' => round((float) $salesTotal, 2),
            'orders' => $orderCount,
            'average' => $average,
        ];
    }

    private function makeSalesPeriodDetail(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd, string $label): array
    {
        $currentMetrics = $this->summariseSalesWindow($currentStart, $currentEnd);
        $previousMetrics = $this->summariseSalesWindow($previousStart, $previousEnd);

        return [
            'label' => $label,
            'range' => [
                'start' => $currentStart->copy()->toDateString(),
                'end' => $currentEnd->copy()->toDateString(),
            ],
            'sales' => $currentMetrics['sales'],
            'orders' => $currentMetrics['orders'],
            'average' => $currentMetrics['average'],
            'previous' => $previousMetrics,
            'salesDelta' => $this->calculateDelta($currentMetrics['sales'], $previousMetrics['sales']),
            'ordersDelta' => $this->calculateDelta($currentMetrics['orders'], $previousMetrics['orders']),
        ];
    }

    private function buildPaymentMethodBreakdown(Carbon $now): array
    {
        $windowStart = $now->copy()->subDays(30)->startOfDay();
        $timestampExpression = 'COALESCE(recorded_at, created_at)';

        $rows = Payment::query()
            ->selectRaw('COALESCE(method, "Unspecified") as method_label, COUNT(*) as usage_count, SUM(amount) as total_amount_sum')
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->where('archived', false)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', '!=', 'pending')
                  ->where('archived', false);
            })
            ->whereRaw("{$timestampExpression} BETWEEN ? AND ?", [$windowStart, $now])
            ->groupBy('method_label')
            ->orderByDesc('total_amount_sum')
            ->limit(5)
            ->get();

        $totalAmount = (float) $rows->sum('total_amount_sum');

        return $rows
            ->map(function (Payment $payment) use ($totalAmount) {
                $total = (float) ($payment->total_amount_sum ?? 0.0);
                $share = $totalAmount > 0.0 ? round(($total / $totalAmount) * 100, 1) : 0.0;

                return [
                    'method' => $payment->method_label,
                    'count' => (int) ($payment->usage_count ?? 0),
                    'total' => round($total, 2),
                    'share' => $share,
                ];
            })
            ->values()
            ->toArray();
    }

    private function buildInventoryMonitorSection(Collection $materials): array
    {
        $lowStockMaterials = $materials
            ->filter(function (Material $material) {
                $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
                $reorder = (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);

                return $stock > 0 && $reorder > 0 && $stock <= $reorder;
            })
            ->sortBy('material_name')
            ->take(10);

        $outOfStockMaterials = $materials
            ->filter(function (Material $material) {
                $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);

                return $stock <= 0;
            })
            ->sortBy('material_name')
            ->take(10);

        $movementLogs = StockMovement::query()
            ->with([
                'material:material_id,material_name,unit',
                'user:user_id,name,email',
            ])
            ->latest('created_at')
            ->take(12)
            ->get();

        return [
            'lowStockMaterials' => $lowStockMaterials,
            'outOfStockMaterials' => $outOfStockMaterials,
            'movementLogs' => $movementLogs,
        ];
    }

    private function buildCustomerInsightsSection(): array
    {
        $now = Carbon::now();
        $recentWindowStart = $now->copy()->subDays(90)->startOfDay();
        $frequencyWindowStart = $now->copy()->subDays(30)->startOfDay();

        $topCustomers = Order::query()
            ->selectRaw('customer_id, COUNT(*) as order_count, SUM(total_amount) as total_spent')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with(['customer:customer_id,first_name,middle_name,last_name'])
            ->get()
            ->map(function (Order $order) {
                $name = optional($order->customer)->name ?? 'Guest';

                return [
                    'name' => $name,
                    'orders' => (int) $order->order_count,
                    'total_spent' => round((float) $order->total_spent, 2),
                ];
            });

        $recentOrders = Order::query()
            ->select(['customer_id', 'order_date', 'created_at'])
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereNotNull('customer_id')
            ->whereRaw('COALESCE(order_date, created_at) >= ?', [$recentWindowStart])
            ->get()
            ->groupBy('customer_id');

        $totalRecentOrders = (int) $recentOrders->flatten(1)->count();
        $uniqueRecentCustomers = (int) $recentOrders->keys()->count();
        $orderFrequency = $uniqueRecentCustomers > 0 ? round($totalRecentOrders / $uniqueRecentCustomers, 2) : 0.0;

        $averageOrderGap = $recentOrders
            ->map(function ($orders) {
                $sorted = $orders
                    ->map(fn ($order) => Carbon::parse($order->order_date ?? $order->created_at))
                    ->filter()
                    ->sort()
                    ->values();

                if ($sorted->count() < 2) {
                    return null;
                }

                $gaps = [];
                for ($i = 1; $i < $sorted->count(); $i++) {
                    $gaps[] = $sorted[$i - 1]->diffInDays($sorted[$i]);
                }

                return collect($gaps)->avg();
            })
            ->filter()
            ->avg();

        $orderGapDays = $averageOrderGap !== null ? round((float) $averageOrderGap, 1) : null;

        $frequencyWindowOrders = Order::query()
            ->selectRaw('CASE 
                WHEN HOUR(COALESCE(order_date, created_at)) BETWEEN 5 AND 11 THEN "Morning"
                WHEN HOUR(COALESCE(order_date, created_at)) BETWEEN 12 AND 16 THEN "Afternoon"
                WHEN HOUR(COALESCE(order_date, created_at)) BETWEEN 17 AND 21 THEN "Evening"
                ELSE "Late Night"
            END as bucket_label, COUNT(*) as total_orders')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereRaw('COALESCE(order_date, created_at) >= ?', [$frequencyWindowStart])
            ->groupBy('bucket_label')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->bucket_label => (int) ($row->total_orders ?? 0),
                ];
            });

        $timeOfDayBuckets = collect(['Morning', 'Afternoon', 'Evening', 'Late Night'])
            ->mapWithKeys(fn ($label) => [$label => (int) ($frequencyWindowOrders[$label] ?? 0)])
            ->all();

        $dayOfWeekRows = Order::query()
            ->selectRaw('DAYOFWEEK(COALESCE(order_date, created_at)) as day_index, COUNT(*) as total_orders')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereRaw('COALESCE(order_date, created_at) >= ?', [$frequencyWindowStart])
            ->groupBy('day_index')
            ->get();

        $dayLabels = [
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday',
        ];

        $dayOfWeekBreakdown = collect($dayLabels)
            ->map(function ($label, $index) use ($dayOfWeekRows) {
                $total = optional($dayOfWeekRows->firstWhere('day_index', (int) $index))->total_orders ?? 0;

                return [
                    'label' => $label,
                    'total' => (int) $total,
                ];
            })
            ->values();

        $repeatCustomersCount = Order::query()
            ->selectRaw('customer_id, COUNT(*) as orders_count')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) >= 2')
            ->count();

        $popularDesigns = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_selections')
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled')
                      ->where('archived', false);
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_selections')
            ->limit(6)
            ->get()
            ->map(function ($orderItem) {
                $product = $orderItem->product()->with('template')->first();
                if (!$product) {
                    return null;
                }

                $image = null;
                if ($product->template && $product->template->front_image) {
                    $image = '/storage/' . $product->template->front_image;
                } elseif ($product->image) {
                    $image = '/storage/' . $product->image;
                }

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'orders' => (int) $orderItem->total_selections,
                    'image' => $image,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $peakOrderDays = Order::query()
            ->selectRaw('DATE(COALESCE(order_date, created_at)) as order_day, COUNT(*) as total_orders')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->groupBy('order_day')
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'day' => Carbon::parse($row->order_day)->format('M d, Y'),
                    'total_orders' => (int) $row->total_orders,
                ];
            });

        return [
            'topCustomers' => $topCustomers,
            'repeatCustomers' => $repeatCustomersCount,
            'popularDesigns' => $popularDesigns,
            'peakOrderDays' => $peakOrderDays,
            'orderFrequency' => [
                'averagePerCustomer' => $orderFrequency,
                'averageGapDays' => $orderGapDays,
                'orderCount' => $totalRecentOrders,
                'customerCount' => $uniqueRecentCustomers,
                'window' => [
                    'start' => $recentWindowStart->toDateString(),
                    'end' => $now->toDateString(),
                ],
                'windowLabel' => 'Last 90 days',
            ],
            'timeOfDayBuckets' => $timeOfDayBuckets,
            'dayOfWeekBreakdown' => $dayOfWeekBreakdown,
        ];
    }

    private function buildAccountControlSection(): array
    {
        $roleBreakdown = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role')
            ->map(fn ($total) => (int) $total)
            ->all();

        $staffStatusBreakdown = Staff::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $recentStaff = Staff::query()
            ->with(['user:user_id,name,email'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        return [
            'roleBreakdown' => $roleBreakdown,
            'staffStatusBreakdown' => $staffStatusBreakdown,
            'recentStaff' => $recentStaff,
        ];
    }

    private function buildSystemShortcuts(): array
    {
        return [
            [
                'label' => 'Manage Orders',
                'icon' => '📦',
                'route' => route('admin.orders.index'),
                'description' => 'Review, update, or archive orders.',
            ],
            [
                'label' => 'Materials & Inventory',
                'icon' => '🧱',
                'route' => route('admin.materials.index'),
                'description' => 'Restock and monitor material levels.',
            ],
            [
                'label' => 'Products & Templates',
                'icon' => '🎨',
                'route' => route('admin.products.index'),
                'description' => 'Add or update product offerings.',
            ],
            [
                'label' => 'Sales Reports',
                'icon' => '📊',
                'route' => route('admin.reports.sales'),
                'description' => 'Download detailed sales exports.',
            ],
            [
                'label' => 'Customer Messages',
                'icon' => '💬',
                'route' => route('admin.messages.index'),
                'description' => 'Respond to customer inquiries.',
            ],
            [
                'label' => 'Account Management',
                'icon' => '🧑‍🤝‍🧑',
                'route' => route('admin.users.index'),
                'description' => 'Add, update, or deactivate staff.',
            ],
            [
                'label' => 'Payments',
                'icon' => '💵',
                'route' => route('admin.payments.index'),
                'description' => 'Record and reconcile payments.',
            ],
            [
                'label' => 'Notifications',
                'icon' => '🔔',
                'route' => route('admin.notifications'),
                'description' => 'Review system alerts and updates.',
            ],
        ];
    }

    private function buildRecentActivityFeed(): array
    {
        $orderActivities = OrderActivity::query()
            ->with(['order:id,order_number'])
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(function (OrderActivity $activity) {
                return [
                    'type' => 'order',
                    'message' => $activity->description,
                    'order' => optional($activity->order)->order_number,
                    'timestamp' => $activity->created_at,
                    'actor' => $activity->user_name,
                ];
            });

        $inventoryActivities = StockMovement::query()
            ->with([
                'material:material_id,material_name',
                'user:user_id,name',
            ])
            ->latest('created_at')
            ->take(6)
            ->get()
            ->map(function (StockMovement $movement) {
                $verb = match ($movement->movement_type) {
                    'restock' => 'restocked',
                    'usage' => 'used',
                    'adjustment' => 'adjusted',
                    default => 'updated',
                };

                return [
                    'type' => 'inventory',
                    'message' => sprintf(
                        '%s %s %d units of %s',
                        $movement->user?->name ?? 'System',
                        $verb,
                        abs((int) $movement->quantity),
                        $movement->material?->material_name ?? 'Unknown material'
                    ),
                    'timestamp' => $movement->created_at,
                ];
            });

        return $orderActivities
            ->merge($inventoryActivities)
            ->sortByDesc('timestamp')
            ->values()
            ->take(12)
            ->all();
    }

    private function buildUpcomingCalendarSection(): array
    {
        $upcoming = Order::query()
            ->select(['id', 'order_number', 'date_needed', 'status', 'total_amount'])
            ->whereNotNull('date_needed')
            ->where('status', '!=', 'cancelled')
            ->where('archived', false)
            ->where('date_needed', '>=', Carbon::now()->startOfDay())
            ->orderBy('date_needed')
            ->get()
            ->map(function (Order $order) {
                return [
                    'order_number' => $order->order_number ?? ('#' . $order->id),
                    'status' => $order->status,
                    'total_amount' => round((float) $order->total_amount, 2),
                    'date_needed' => optional($order->date_needed)->format('M d, Y'),
                ];
            });

        return [
            'upcomingOrders' => $upcoming,
            'calendarRoute' => route('admin.reports.pickup-calendar'),
        ];
    }

    private function buildMaterialAlerts(Collection $materials): array
    {
        $alerts = [];

        $criticalMaterials = $materials
            ->filter(fn (Material $material) => (int) (optional($material->inventory)->stock_level ?? 0) <= 0)
            ->pluck('material_name')
            ->filter()
            ->values();

        if ($criticalMaterials->isNotEmpty()) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Some materials are out of stock and may block incoming orders.',
                'items' => $criticalMaterials->take(5)->all(),
            ];
        }

        $lowMaterials = $materials
            ->filter(function (Material $material) {
                $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
                $reorder = (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);

                return $stock > 0 && $reorder > 0 && $stock <= $reorder;
            })
            ->pluck('material_name')
            ->filter()
            ->values();

        if ($lowMaterials->isNotEmpty()) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Materials nearing depletion. Plan restock soon.',
                'items' => $lowMaterials->take(5)->all(),
            ];
        }

        return $alerts;
    }

    private function resolveDashboardAnnouncements(): array
    {
        return [];
    }

    private function buildCustomerReviewSnapshot(): array
    {
        $averageRating = OrderRating::query()->avg('rating');
        $totalReviews = OrderRating::query()->count();

        return [
            'average' => $averageRating ? round($averageRating, 1) : null,
            'count' => $totalReviews,
        ];
    }

    private function dashboardStatusOptions(): array
    {
        return [
            'draft' => 'New Order',
            'pending' => 'Order Received',
            'pending_awaiting_materials' => 'Pending – Awaiting Materials',
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'confirmed' => 'Ready for Pickup',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    private function buildSalesTrendDataset(int $weeks = 8): array
    {
        $weeks = max(1, $weeks);

        $now = Carbon::now();
        $currentWeekEnd = $now->copy()->endOfWeek();
        $firstWeekStart = $currentWeekEnd->copy()->subWeeks($weeks - 1)->startOfWeek();

        $ordersInWindow = Order::query()
            ->select(['id', 'total_amount', 'summary_snapshot', 'metadata', 'order_date', 'created_at'])
            ->where('status', 'completed')
            ->whereRaw('DATE(COALESCE(order_date, created_at)) BETWEEN ? AND ?', [
                $firstWeekStart->toDateString(),
                $currentWeekEnd->toDateString(),
            ])
            ->get();

        $weeklyTotals = $ordersInWindow
            ->groupBy(function (Order $order) {
                $timestamp = $order->order_date ?? $order->created_at;

                if (!$timestamp) {
                    return null;
                }

                return Carbon::parse($timestamp)->startOfWeek()->toDateString();
            })
            ->filter(function ($group, $key) {
                return !is_null($key);
            })
            ->map(function ($orders) {
                return $orders->sum(function (Order $order) {
                    $amount = (float) ($order->total_amount ?? 0);

                    if ($amount > 0) {
                        return $amount;
                    }

                    return (float) $order->grandTotalAmount();
                });
            });

        $labels = [];
        $values = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $firstWeekStart->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $bucketKey = $weekStart->toDateString();

            $labels[] = 'Week of ' . $weekStart->format('M d');
            $values[] = round((float) ($weeklyTotals->get($bucketKey, 0)), 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function summariseInventory(Collection $materials): array
    {
        $lowStock = 0;
        $outStock = 0;
        $totalStock = 0;
        $dailyConsumption = 0.0;

        $recentOrders = Order::query()
            ->select(['id'])
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->with(['items:id,order_id,quantity'])
            ->get();

        $days = max(1, $recentOrders->pluck('created_at')->count() > 0 ? 30 : 7);
        $totalOrderedUnits = $recentOrders
            ->flatMap(fn ($order) => $order->items)
            ->sum('quantity');
        $dailyConsumption = $totalOrderedUnits > 0 ? $totalOrderedUnits / $days : 0.0;

        foreach ($materials as $material) {
            $stock = (int) (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0);
            $reorder = (int) (optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0);

            $totalStock += $stock;

            if ($stock <= 0) {
                $outStock++;
            } elseif ($reorder > 0 && $stock <= $reorder) {
                $lowStock++;
            }
        }

        $riskPercent = 0;
        if ($materials->count() > 0) {
            $riskPercent = round((($lowStock + $outStock) / max(1, $materials->count())) * 100, 1);
        }

        $coverageDays = $dailyConsumption > 0
            ? round($totalStock / $dailyConsumption, 1)
            : null;

        return [
            'totalSkus' => $materials->count(),
            'lowStock' => $lowStock,
            'outStock' => $outStock,
            'totalStock' => $totalStock,
            'riskPercent' => $riskPercent,
            'coverageDays' => $coverageDays,
        ];
    }

    private function calculateDelta(float|int $current, float|int $previous): array
    {
        $currentValue = (float) $current;
        $previousValue = (float) $previous;
        $difference = $currentValue - $previousValue;

        $percent = $previousValue == 0.0
            ? ($currentValue > 0 ? 100.0 : 0.0)
            : round(($difference / $previousValue) * 100, 1);

        return [
            'value' => $currentValue,
            'change' => round($difference, 2),
            'percent' => $percent,
            'direction' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat'),
        ];
    }

    private function resolvePopularDesign(): ?array
    {
        $topRow = OrderItem::query()
            ->selectRaw('COALESCE(product_id, 0) as resolved_product_id, product_name, SUM(quantity) as total_quantity, COUNT(DISTINCT order_id) as order_count')
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->where(function ($query) {
                $query->whereNull('line_type')
                    ->orWhere('line_type', OrderItem::LINE_TYPE_INVITATION);
            })
            ->groupBy(DB::raw('COALESCE(product_id, 0)'), 'product_name')
            ->orderByDesc('total_quantity')
            ->orderByDesc('order_count')
            ->limit(1)
            ->first();

        if (!$topRow) {
            return null;
        }

        $productId = (int) $topRow->resolved_product_id;
        $product = null;
        if ($productId > 0) {
            $product = Product::query()->with('images')->find($productId);
        }

        $referenceItem = OrderItem::query()
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->when($productId > 0, function ($query) use ($productId) {
                $query->where('product_id', $productId);
            }, function ($query) use ($topRow) {
                $query->where('product_name', $topRow->product_name);
            })
            ->latest('created_at')
            ->first();

        $preview = $this->extractDesignPreview($product, $referenceItem);
        $previewUrl = $this->normalizeMediaUrl($preview);

        return [
            'name' => $topRow->product_name ?: 'Custom Design',
            'orders' => (int) $topRow->order_count,
            'quantity' => (int) $topRow->total_quantity,
            'image' => $previewUrl,
            'product' => $product,
        ];
    }

    private function extractDesignPreview(?Product $product, ?OrderItem $referenceItem): ?string
    {
        if ($product) {
            $imageCandidates = [
                $product->image ?? null,
                optional($product->images)->front,
                optional($product->images)->primary,
                optional($product->images)->preview,
            ];

            foreach ($imageCandidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    return $candidate;
                }
            }
        }

        $metadata = $referenceItem?->design_metadata;
        if (is_array($metadata)) {
            $metaCandidates = [
                data_get($metadata, 'preview'),
                data_get($metadata, 'image'),
                data_get($metadata, 'primary'),
                data_get($metadata, 'primary_image'),
                data_get($metadata, 'thumbnail'),
                data_get($metadata, 'gallery.0'),
                data_get($metadata, 'images.0'),
            ];

            foreach ($metaCandidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    return $candidate;
                }
            }

            foreach ($metadata as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }

                if (is_array($value)) {
                    foreach ($value as $inner) {
                        if (is_string($inner) && trim($inner) !== '') {
                            return $inner;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function normalizeMediaUrl(?string $path): ?string
    {
        if (!$path || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        if (Str::startsWith($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        if (Str::startsWith($cleanPath, 'public/')) {
            $converted = Str::replaceFirst('public/', 'storage/', $cleanPath);
            return asset($converted);
        }

        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    // Show profile info
    public function show()
    {
        /** @var User $admin */
        $admin = Auth::user()->load(['staff', 'address']);

        $this->attachAdminStaffRecord($admin);

        return view('admin.profile.show', compact('admin'));
    }

    // Show edit form
    public function edit()
    {
        /** @var User $admin */
        $admin = Auth::user()->load(['staff', 'address']);

        $this->attachAdminStaffRecord($admin);

        return view('admin.profile.edit', compact('admin'));
    }

    // Update admin info (users + staff + address)
    public function update(Request $request)
    {
        /** @var User $admin */
        $admin = Auth::user()->load(['staff', 'address']);

        $this->attachAdminStaffRecord($admin, $request);

        // ✅ Validation
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'address'        => 'nullable|string|max:255',
            'password'       => 'nullable|min:6|confirmed', // expects password_confirmation
            'profile_pic'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        // ✅ Update users table (only optional password). Email stays fixed.
        if (!empty($request->password)) {
            $admin->update([
                'password' => Hash::make($request->password),
            ]);
        }

        // ✅ Ensure we are updating the dedicated admin staff record (ID 6112)
        $this->attachAdminStaffRecord($admin);

        if ($admin->staff) {
            $staffUpdateData = [
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
                'address'        => $request->address,
                'role'           => 'admin',
            ];

            // Handle profile picture upload
            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('staff_profiles', 'public');
                $staffUpdateData['profile_pic'] = $path;
            }

            $admin->staff->update($staffUpdateData);
        }

        

        return redirect()->route('admin.profile.show')
                         ->with('success', 'Profile updated successfully.');
    }

    /**
     * Ensure we attach the dedicated admin staff row (ID 6112).
     */
    private function attachAdminStaffRecord(User $admin, ?Request $request = null): void
    {
        $adminStaffId = 6112;

        // Prefer the dedicated admin staff record when it exists
        $adminStaff = Staff::find($adminStaffId);
        if ($adminStaff) {
            $admin->setRelation('staff', $adminStaff);
            return;
        }

        // Otherwise ensure there is at least one staff record linked to this admin
        if (!$admin->staff) {
            $adminStaff = $admin->staff()->create([
                'first_name' => $request?->first_name ?: 'Super',
                'last_name' => $request?->last_name ?: 'Admin',
                'role' => 'admin',
                'contact_number' => $request?->contact_number ?: '0917-000-0000',
            ]);
            $admin->setRelation('staff', $adminStaff);
        }
    }

    public function notifications()
    {
        /** @var \App\Models\User|null $admin */
    /** @var User|null $admin */
    $admin = Auth::user();

        if ($admin) {
            $admin->unreadNotifications()->update(['read_at' => now()]);
        }

        $notifications = $admin
            ? $admin->notifications()->latest()->get()
            : collect();

        return view('admin.notifications.index', compact('notifications'));
    }

    
}
