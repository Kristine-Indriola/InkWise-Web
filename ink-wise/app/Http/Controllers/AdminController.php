<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

        $inventorySummary = $this->summariseInventory($materials);

        $dashboardMetrics = [
            'ordersThisWeek' => $ordersThisWeek,
            'revenueThisWeek' => round((float) $revenueThisWeek, 2),
            'averageOrderValue' => $averageOrderValue,
            'pendingOrders' => $pendingOrders,
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

        return view('admin.dashboard', compact('materials', 'dashboardMetrics', 'popularDesign'));
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
        $admin = Auth::user(); // current logged-in admin
        return view('admin.profile.show', compact('admin'));
    }

    // Show edit form
    public function edit()
    {
        /** @var User $admin */
        $admin = Auth::user();
        return view('admin.profile.edit', compact('admin'));
    }

    // Update admin info (users + staff + address)
    public function update(Request $request)
    {
        /** @var User $admin */
        $admin = Auth::user();

        // ✅ Validation
        $request->validate([
            'email'          => 'required|email|unique:users,email,' . $admin->user_id . ',user_id',
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'password'       => 'nullable|min:6|confirmed', // expects password_confirmation
            // Address validation
            'street'         => 'nullable|string|max:255',
            'barangay'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'province'       => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
        ]);

        // ✅ Update users table (email + optional password)
        $updateData = ['email' => $request->email];
        if (!empty($request->password)) {
            $updateData['password'] = Hash::make($request->password);
        }
        $admin->update($updateData);

        // ✅ Update staff table
        if ($admin->staff) {
            $admin->staff->update([
                'first_name'     => $request->first_name,
                'middle_name'    => $request->middle_name,
                'last_name'      => $request->last_name,
                'contact_number' => $request->contact_number,
            ]);
        }

        // ✅ Update or create address table
        if ($admin->address) {
            $admin->address->update([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        } else {
            $admin->address()->create([
                'street'      => $request->street,
                'barangay'    => $request->barangay,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);
        }

        

        return redirect()->route('admin.profile.show')
                         ->with('success', 'Profile updated successfully.');
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
