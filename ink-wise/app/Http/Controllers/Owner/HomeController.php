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

        $completedOrders = Order::query()
            ->select(['id', 'total_amount', 'summary_snapshot', 'metadata'])
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
            'totalRevenue'        => $totalRevenue,
        ]);
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
}
