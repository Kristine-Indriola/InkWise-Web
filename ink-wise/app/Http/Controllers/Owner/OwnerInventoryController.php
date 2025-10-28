<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ink;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OwnerInventoryController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderInventoryPage($request);
    }

    public function track(Request $request)
    {
        return $this->renderInventoryPage($request);
    }

    protected function renderInventoryPage(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $request->input('stock') ?? $request->input('status');

        $inventoryItems = $this->buildInventoryCollection($search);

        $counts = [
            'total' => $inventoryItems->count(),
            'low' => $inventoryItems->where('status.slug', 'low')->count(),
            'out' => $inventoryItems->where('status.slug', 'out')->count(),
        ];
        $counts['in'] = max(0, $counts['total'] - ($counts['low'] + $counts['out']));

        $filteredItems = $inventoryItems;
        if ($statusFilter && in_array($statusFilter, ['in', 'low', 'out'], true)) {
            $filteredItems = $inventoryItems
                ->where('status.slug', $statusFilter)
                ->values();
        }

        return view('owner.inventory-track', [
            'inventoryItems' => $filteredItems,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    protected function buildInventoryCollection(?string $search = null): Collection
    {
        $materials = Material::with('inventory')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('material_name', 'like', "%{$search}%")
                        ->orWhere('material_type', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(function (Material $material) {
                return $this->transformMaterial($material);
            });

        $inks = Ink::with('inventory')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('material_name', 'like', "%{$search}%")
                        ->orWhere('ink_color', 'like', "%{$search}%")
                        ->orWhere('material_type', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(function (Ink $ink) {
                return $this->transformInk($ink);
            });

        return $materials
            ->concat($inks)
            ->sortBy('item_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    protected function transformMaterial(Material $material): array
    {
        $stock = (int) ($material->inventory->stock_level ?? $material->stock_qty ?? 0);
        $reorder = $material->inventory->reorder_level ?? $material->reorder_point ?? null;

        return [
            'id' => 'material-' . $material->material_id,
            'source' => 'material',
            'item_name' => $material->material_name,
            'category' => $material->material_type ?? 'Material',
            'stock_level' => $stock,
            'reorder_level' => $reorder !== null ? (int) $reorder : null,
            'status' => $this->determineStatus($stock, $reorder),
        ];
    }

    protected function transformInk(Ink $ink): array
    {
        $stock = (int) ($ink->inventory->stock_level ?? $ink->stock_qty ?? 0);
        $reorder = $ink->inventory->reorder_level ?? null;

        return [
            'id' => 'ink-' . $ink->id,
            'source' => 'ink',
            'item_name' => $ink->material_name,
            'category' => $ink->material_type ?? 'Ink',
            'stock_level' => $stock,
            'reorder_level' => $reorder !== null ? (int) $reorder : null,
            'status' => $this->determineStatus($stock, $reorder),
        ];
    }

    protected function determineStatus(int $stock, $reorderLevel): array
    {
        if ($stock <= 0) {
            return ['slug' => 'out', 'label' => 'Out of Stock'];
        }

        if (!is_null($reorderLevel) && (int) $reorderLevel > 0 && $stock <= (int) $reorderLevel) {
            return ['slug' => 'low', 'label' => 'Low Stock'];
        }

        return ['slug' => 'in', 'label' => 'In Stock'];
    }

}
