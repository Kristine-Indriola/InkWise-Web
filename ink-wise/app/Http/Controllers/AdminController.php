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
            ->whereRaw('COALESCE(order_date, created_at) BETWEEN ? AND ?', [$weekStart, $now]);

        $previousWeekStart = $weekStart->copy()->subWeek();
        $previousWeekEnd = $weekStart->copy()->subSecond();
        $previousWeekOrdersQuery = Order::query()
            ->where('status', '!=', 'cancelled')
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
            ->count();

        $newOrders = Order::query()
            ->where('status', 'draft')
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
            ->count();

        $totalSales = (float) Order::query()
            ->where('status', '!=', 'cancelled')
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
        $coalesceExpression = 'DATE(COALESCE(order_date, created_at))';

        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $baseQuery = fn () => Order::query()
            ->where('status', 'completed');

        $dailySales = (clone $baseQuery())
            ->whereRaw("{$coalesceExpression} = ?", [$today->toDateString()])
            ->sum('total_amount');

        $weeklySales = (clone $baseQuery())
            ->whereRaw("{$coalesceExpression} BETWEEN ? AND ?", [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->sum('total_amount');

        $monthlySales = (clone $baseQuery())
            ->whereRaw("{$coalesceExpression} BETWEEN ? AND ?", [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('total_amount');

        $trend = $this->buildSalesTrendDataset();

        $bestSelling = OrderItem::query()
            ->selectRaw('COALESCE(product_name, "Custom Design") as label, SUM(quantity) as quantity, COUNT(DISTINCT order_id) as orders_count')
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->groupBy('label')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        $recentTransactions = Payment::query()
            ->with([
                'order:id,order_number,status',
                'recordedBy:user_id,name',
            ])
            ->latest('recorded_at')
            ->latest()
            ->take(6)
            ->get();

        return [
            'daily' => round((float) $dailySales, 2),
            'weekly' => round((float) $weeklySales, 2),
            'monthly' => round((float) $monthlySales, 2),
            'trend' => $trend,
            'bestSelling' => $bestSelling,
            'recentTransactions' => $recentTransactions,
        ];
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
        $topCustomers = Customer::query()
            ->withCount(['orders as order_count' => function ($query) {
                $query->where('status', '!=', 'cancelled');
            }])
            ->withSum(['orders as total_spent' => function ($query) {
                $query->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'name' => $customer->name,
                    'orders' => (int) $customer->order_count,
                    'total_spent' => round((float) $customer->total_spent, 2),
                ];
            });

        $repeatCustomersCount = Customer::query()
            ->whereHas('orders', function ($query) {
                $query->where('status', '!=', 'cancelled');
            }, '>=', 2)
            ->count();

        $popularDesigns = OrderItem::query()
            ->selectRaw('COALESCE(product_name, "Custom Design") as label, SUM(quantity) as quantity')
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->groupBy('label')
            ->orderByDesc('quantity')
            ->limit(6)
            ->pluck('quantity', 'label')
            ->map(fn ($qty) => (int) $qty)
            ->all();

        $peakOrderDays = Order::query()
            ->selectRaw('DATE(COALESCE(order_date, created_at)) as order_day, COUNT(*) as total_orders')
            ->where('status', '!=', 'cancelled')
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
            ->where('date_needed', '>=', Carbon::now()->startOfDay())
            ->orderBy('date_needed')
            ->take(6)
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

    private function buildSalesTrendDataset(int $days = 14): array
    {
        $days = max(1, $days);

        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $dateRange = collect(range(0, $days - 1))->map(fn ($offset) => $startDate->copy()->addDays($offset));

        $rawData = Order::query()
            ->selectRaw('DATE(COALESCE(order_date, created_at)) as bucket_day, SUM(total_amount) as total_sales')
            ->where('status', 'completed')
            ->whereRaw('DATE(COALESCE(order_date, created_at)) >= ?', [$startDate->toDateString()])
            ->groupBy('bucket_day')
            ->pluck('total_sales', 'bucket_day');

        $labels = [];
        $values = [];

        foreach ($dateRange as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $values[] = round((float) ($rawData[$key] ?? 0), 2);
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
