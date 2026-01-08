<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRating;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $newOrdersCount = Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $pendingOrdersCount = Order::query()
            ->where('status', 'pending')
            ->count();

        $lowStockCount = Material::query()
            ->whereHas('inventory', function ($query) {
                $query->whereColumn('stock_level', '<=', 'reorder_level')
                    ->where('stock_level', '>', 0);
            })
            ->count();

        $averageRating = OrderRating::query()->avg('rating');
        $averageRating = $averageRating ? round($averageRating, 2) : null;
        $totalRatings = OrderRating::query()->count();

        $topSellingRows = OrderItem::query()
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as total_sold')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'in_production', 'completed']);
            })
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $topSellingProducts = [
            'labels' => $topSellingRows
                ->map(function ($row) {
                    if (!empty($row->product_name)) {
                        return $row->product_name;
                    }

                    if (!empty($row->product_id)) {
                        return 'Product #' . $row->product_id;
                    }

                    return 'Unnamed Product';
                })
                ->values()
                ->toArray(),
            'data' => $topSellingRows
                ->pluck('total_sold')
                ->map(fn ($qty) => (int) $qty)
                ->values()
                ->toArray(),
        ];

        $inventoryMovement = $this->buildInventoryMovementTrend();
        $weeklySalesTrend = $this->buildWeeklySalesTrend();
        $customerBehavior = $this->buildCustomerBehaviorInsights();

        $completedOrders = Order::query()
            ->select(['id', 'total_amount', 'summary_snapshot', 'metadata', 'customer_id', 'customer_order_id', 'created_at'])
            ->where('status', 'completed')
            ->get();

        $totalRevenue = round($completedOrders->sum(function (Order $order) {
            return max($order->grandTotalAmount(), 0.0);
        }), 2);

        return view('owner.owner-home', [
            'newOrdersCount'      => $newOrdersCount,
            'pendingOrdersCount'  => $pendingOrdersCount,
            'lowStockCount'       => $lowStockCount,
            'averageRating'       => $averageRating,
            'totalRatings'        => $totalRatings,
            'topSellingProducts'  => $topSellingProducts,
            'inventoryMovement'   => $inventoryMovement,
            'weeklySalesTrend'    => $weeklySalesTrend,
            'totalRevenue'        => $totalRevenue,
            'customerBehavior'    => $customerBehavior,
        ]);
    }

    protected function buildCustomerBehaviorInsights(): array
    {
        $completedOrders = Order::query()
            ->where('status', 'completed')
            ->get();

        $popularDesignRows = OrderItem::query()
            ->selectRaw('order_items.product_id, order_items.product_name, COUNT(DISTINCT order_items.order_id) as order_count, SUM(order_items.quantity) as total_quantity')
            ->whereHas('order', function ($query) {
                $query->where('status', 'completed');
            })
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $popularDesigns = $popularDesignRows
            ->map(function ($row) {
                $identifier = null;

                if (!empty($row->product_name)) {
                    $identifier = $row->product_name;
                } elseif (!empty($row->product_id)) {
                    $identifier = 'Design #' . $row->product_id;
                }

                return [
                    'name' => $identifier ?? 'Unknown Design',
                    'order_count' => (int) $row->order_count,
                    'units_sold' => (int) $row->total_quantity,
                    'product_id' => $row->product_id,
                ];
            })
            ->values()
            ->toArray();

        $now = Carbon::now();
        $ordersLast30Days = $completedOrders
            ->filter(function (Order $order) use ($now) {
                return $order->created_at instanceof Carbon && $order->created_at->greaterThanOrEqualTo($now->copy()->subDays(30));
            })
            ->count();

        $ordersGroupedByCustomer = $completedOrders->groupBy(function (Order $order) {
            if (!empty($order->customer_id)) {
                return 'customer:' . $order->customer_id;
            }

            if (!empty($order->customer_order_id)) {
                return 'customer_order:' . $order->customer_order_id;
            }

            return 'order:' . $order->id;
        });

        $totalCustomers = $ordersGroupedByCustomer->count();
        $repeatCustomers = $ordersGroupedByCustomer
            ->filter(function ($orders) {
                return $orders instanceof \Illuminate\Support\Collection && $orders->count() > 1;
            })
            ->count();

        $totalCompletedOrders = $completedOrders->count();
        $averageOrdersPerCustomer = $totalCustomers > 0
            ? round($totalCompletedOrders / $totalCustomers, 2)
            : 0.0;

        $repeatCustomerRate = $totalCustomers > 0
            ? round(($repeatCustomers / $totalCustomers) * 100, 1)
            : 0.0;

        $recentOrders = $completedOrders
            ->filter(function (Order $order) use ($now) {
                return $order->created_at instanceof Carbon && $order->created_at->greaterThanOrEqualTo($now->copy()->subDays(90));
            })
            ->values();

        $dayOfWeekBreakdown = $recentOrders
            ->groupBy(function (Order $order) {
                if ($order->created_at instanceof Carbon) {
                    return $order->created_at->format('l');
                }

                return 'Unknown';
            })
            ->map->count()
            ->toArray();

        $chartDayOfWeekBreakdown = $dayOfWeekBreakdown;

        $topOrderDay = null;
        if (!empty($dayOfWeekBreakdown)) {
            $sortedDayBreakdown = $dayOfWeekBreakdown;
            arsort($sortedDayBreakdown);
            $topOrderDay = array_key_first($sortedDayBreakdown);
        }

        $timeOfDayBuckets = [
            'Early Morning (5 AM - 11 AM)' => 0,
            'Midday (11 AM - 4 PM)' => 0,
            'Evening (4 PM - 9 PM)' => 0,
            'Late Night (9 PM - 5 AM)' => 0,
        ];

        foreach ($recentOrders as $order) {
            if (!($order->created_at instanceof Carbon)) {
                continue;
            }

            $hour = (int) $order->created_at->format('H');

            if ($hour >= 5 && $hour < 11) {
                $timeOfDayBuckets['Early Morning (5 AM - 11 AM)']++;
            } elseif ($hour >= 11 && $hour < 16) {
                $timeOfDayBuckets['Midday (11 AM - 4 PM)']++;
            } elseif ($hour >= 16 && $hour < 21) {
                $timeOfDayBuckets['Evening (4 PM - 9 PM)']++;
            } else {
                $timeOfDayBuckets['Late Night (9 PM - 5 AM)']++;
            }
        }

        $topOrderTimeWindow = null;
        $nonZeroBuckets = array_filter($timeOfDayBuckets, fn ($count) => $count > 0);
        if (!empty($nonZeroBuckets)) {
            arsort($nonZeroBuckets);
            $topOrderTimeWindow = array_key_first($nonZeroBuckets);
        }

        $averageOrderValue = $totalCompletedOrders > 0
            ? round($completedOrders->avg(function (Order $order) {
                return max($order->grandTotalAmount(), 0.0);
            }), 2)
            : 0.0;

        $popularDesignChart = [
            'labels' => array_map(fn ($design) => $design['name'], $popularDesigns),
            'units' => array_map(fn ($design) => $design['units_sold'], $popularDesigns),
            'orders' => array_map(fn ($design) => $design['order_count'], $popularDesigns),
        ];

        $repeatDistribution = [
            'repeat_customers' => $repeatCustomers,
            'single_customers' => max($totalCustomers - $repeatCustomers, 0),
        ];

        $dayOrdering = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $orderedDayLabels = [];
        $orderedDayCounts = [];

        foreach ($dayOrdering as $day) {
            if (array_key_exists($day, $chartDayOfWeekBreakdown)) {
                $orderedDayLabels[] = $day;
                $orderedDayCounts[] = (int) $chartDayOfWeekBreakdown[$day];
                unset($chartDayOfWeekBreakdown[$day]);
            }
        }

        foreach ($chartDayOfWeekBreakdown as $label => $count) {
            $orderedDayLabels[] = $label;
            $orderedDayCounts[] = (int) $count;
        }

        return [
            'popular_designs' => $popularDesigns,
            'order_frequency' => [
                'orders_last_30_days' => $ordersLast30Days,
                'average_orders_per_customer' => $averageOrdersPerCustomer,
                'repeat_customer_rate' => $repeatCustomerRate,
                'total_customers' => $totalCustomers,
            ],
            'buying_patterns' => [
                'top_order_day' => $topOrderDay,
                'top_order_time_window' => $topOrderTimeWindow,
                'average_order_value' => $averageOrderValue,
                'day_of_week_breakdown' => $dayOfWeekBreakdown,
                'time_of_day_breakdown' => $timeOfDayBuckets,
            ],
            'chart_data' => [
                'popular_designs' => $popularDesignChart,
                'repeat_distribution' => $repeatDistribution,
                'day_of_week' => [
                    'labels' => $orderedDayLabels,
                    'counts' => $orderedDayCounts,
                ],
                'time_of_day' => [
                    'labels' => array_keys($timeOfDayBuckets),
                    'counts' => array_map('intval', array_values($timeOfDayBuckets)),
                ],
            ],
        ];
    }

    protected function buildInventoryMovementTrend(int $weeks = 6): array
    {
        $weeks = max(1, $weeks);

        $endOfCurrentWeek = Carbon::now()->endOfWeek();
        $startOfRange = $endOfCurrentWeek->copy()->startOfWeek()->subWeeks($weeks - 1);

        $movements = StockMovement::query()
            ->where('created_at', '>=', $startOfRange)
            ->get();

        $grouped = $movements->groupBy(function (StockMovement $movement) {
            $createdAt = $movement->created_at ?? Carbon::now();

            if (!$createdAt instanceof Carbon) {
                $createdAt = Carbon::parse($createdAt);
            }

            return $createdAt->copy()->startOfWeek()->format('Y-m-d');
        });

        $labels = [];
        $incoming = [];
        $outgoing = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $startOfRange->copy()->addWeeks($i);
            $weekKey = $weekStart->format('Y-m-d');
            $bucket = $grouped->get($weekKey, collect());

            $incomingSum = $bucket->sum(function (StockMovement $movement) {
                $quantity = (int) $movement->quantity;

                if ($movement->movement_type === 'restock') {
                    return max($quantity, 0);
                }

                if ($movement->movement_type === 'adjustment' && $quantity > 0) {
                    return $quantity;
                }

                return 0;
            });

            $outgoingSum = $bucket->sum(function (StockMovement $movement) {
                $quantity = (int) $movement->quantity;

                // Accept both legacy and current movement type keys for usage
                if (in_array($movement->movement_type, ['usage', 'used', 'issued', 'sold'], true)) {
                    return max($quantity, 0);
                }

                if ($movement->movement_type === 'adjustment' && $quantity < 0) {
                    return abs($quantity);
                }

                return 0;
            });

            $labels[] = $weekStart->format('M j');
            $incoming[] = $incomingSum;
            $outgoing[] = $outgoingSum;
        }

        return [
            'labels' => $labels,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ];
    }

    protected function buildWeeklySalesTrend(int $weeks = 6): array
    {
        $weeks = max(1, $weeks);

        $endOfCurrentWeek = Carbon::now()->endOfWeek();
        $startOfRange = $endOfCurrentWeek->copy()->startOfWeek()->subWeeks($weeks - 1);

        $orders = Order::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startOfRange)
            ->get();

        $grouped = $orders->groupBy(function (Order $order) {
            $createdAt = $order->created_at ?? Carbon::now();

            if (!$createdAt instanceof Carbon) {
                $createdAt = Carbon::parse($createdAt);
            }

            return $createdAt->copy()->startOfWeek()->format('Y-m-d');
        });

        $labels = [];
        $totals = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $startOfRange->copy()->addWeeks($i);
            $weekKey = $weekStart->format('Y-m-d');
            $bucket = $grouped->get($weekKey, collect());

            $weekTotal = $bucket->sum(function (Order $order) {
                return max($order->grandTotalAmount(), 0.0);
            });

            $labels[] = $weekStart->format('M j');
            $totals[] = round($weekTotal, 2);
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }
}
