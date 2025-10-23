<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;

use App\Models\Material;
use App\Models\Inventory;
use App\Models\Ink; // <-- Add this import
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\StockNotification;
use App\Notifications\MaterialRestockedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function create()
    {
        return view('admin.materials.create');
    }

    public function store(Request $request)
    {
        // Normalize occasion inputs to a canonical token before validation.
        // The UI submits "ALL OCCASION" — map that to 'all' so validation rules accept it.
        if ($request->has('occasion')) {
            $occs = $request->input('occasion');
            if (is_array($occs)) {
                $occs = array_map(function($o) {
                    if (!is_string($o)) return $o;
                    $trim = trim($o);
                    // Accept several variants from the front-end
                    if (strcasecmp($trim, 'ALL OCCASION') === 0 || strcasecmp($trim, 'ALL_OCCASION') === 0 || strcasecmp($trim, 'all') === 0) {
                        return 'all';
                    }
                    return $trim;
                }, $occs);
                $request->merge(['occasion' => $occs]);
            }
        }
        $validated = $request->validate([
            'material_name' => [
                'required','string','max:255',
                // Unique scoped to material_type so same name can exist under different types
                Rule::unique('materials')->where(function ($query) use ($request) {
                    return $query->where('material_type', $request->input('material_type'));
                }),
            ],
            'occasion' => 'required|array|min:1',
            // allow 'all' so the front-end 'All Occasions' option validates
            'occasion.*' => 'string|in:all,wedding,birthday,baptism,corporate',
            'product_type' => 'required|string|in:invitation,giveaway',
            'material_type' => 'nullable|string|max:50',  // ✅ Nullable, free text
            'size' => 'nullable|string|max:50',  // For invitations
            'color' => 'nullable|string|max:50',  // For invitations
            'weight_gsm' => 'nullable|integer|min:0',  // For invitations
            'volume_ml' => 'nullable|numeric|min:0',  // For inks in materials (rare, but included)
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'stock_qty' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        // If 'all' selected, store all occasions as comma-separated values in the occasion column.
        $selected = $validated['occasion'];
        if (in_array('all', $selected)) {
            $allOccasions = ['wedding','birthday','baptism','corporate'];
            $occasionValue = implode(',', $allOccasions);
        } else {
            $occasionValue = implode(',', $selected);
        }

        // Create a single material row with the occasion column containing CSV values.
        $material = Material::create([
            'material_name' => $validated['material_name'],
            'occasion' => $occasionValue,
                'product_type' => $validated['product_type'],
                'material_type' => $validated['material_type'],  // ✅ From form, can be null or custom
                'size' => $validated['size'] ?? null,
                'color' => $validated['color'] ?? null,
                'weight_gsm' => $validated['weight_gsm'] ?? null,
                'volume_ml' => $validated['volume_ml'] ?? null,
                'unit' => $validated['unit'],
                'unit_cost' => $validated['unit_cost'],
                'stock_qty' => $validated['stock_qty'],
                'reorder_point' => $validated['reorder_point'],
                'description' => $validated['description'] ?? null,
                'date_updated' => now(),
            ]);
        // ✅ Now $material is defined and can be used
        $material->inventory()->create([
            'stock_level' => $validated['stock_qty'],
            'reorder_level' => $validated['reorder_point'],
            'remarks' => $validated['stock_qty'] <= 0 ? 'Out of Stock' : ($validated['stock_qty'] > 0 && $validated['stock_qty'] <= $validated['reorder_point'] ? 'Low Stock' : 'In Stock'),
        ]);

        return redirect()->route('admin.materials.index')->with('success', 'Materials added successfully!');
    }

    public function index(Request $request)
    {
        $materialsQuery = Material::with('inventory');

        // Status filters applied to materials' inventory
        if ($request->status === 'low') {
            $materialsQuery->whereHas('inventory', function($q) {
                $q->whereColumn('stock_level', '<=', 'reorder_level')
                  ->where('stock_level', '>', 0);
            });
        }

        if ($request->status === 'out') {
            $materialsQuery->whereHas('inventory', function($q) {
                $q->where('stock_level', '<=', 0);
            });
        }

        // Occasion filter: if occasion is provided, match CSV-stored occasions using FIND_IN_SET
        if ($request->filled('occasion')) {
            $occasion = $request->occasion;
            if ($occasion !== 'all') {
                $materialsQuery->whereRaw("FIND_IN_SET(?, occasion)", [$occasion]);
            } else {
                // explicit 'all' filter: find rows where occasion contains all defined options
                $materialsQuery->where(function($q) {
                    $q->whereRaw("FIND_IN_SET('wedding', occasion)")
                      ->whereRaw("FIND_IN_SET('birthday', occasion)")
                      ->whereRaw("FIND_IN_SET('baptism', occasion)")
                      ->whereRaw("FIND_IN_SET('corporate', occasion)");
                });
            }
        }

        // Search filter for materials
        if ($request->filled('search')) {
            $search = $request->search;
            $materialsQuery->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                  ->orWhere('material_type', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        $materials = $materialsQuery->get();

        // Build inks query so occasion and search filters can apply consistently
        $inksQuery = Ink::with('inventory');

        if ($request->status === 'low') {
            $inksQuery->whereHas('inventory', function($q) {
                $q->whereColumn('stock_level', '<=', 'reorder_level')
                  ->where('stock_level', '>', 0);
            });
        }

        if ($request->status === 'out') {
            $inksQuery->whereHas('inventory', function($q) {
                $q->where('stock_level', '<=', 0);
            });
        }

        if ($request->filled('occasion')) {
            $occasion = $request->occasion;
            if ($occasion !== 'all') {
                $inksQuery->whereRaw("FIND_IN_SET(?, occasion)", [$occasion]);
            } else {
                $inksQuery->where(function($q) {
                    $q->whereRaw("FIND_IN_SET('wedding', occasion)")
                      ->whereRaw("FIND_IN_SET('birthday', occasion)")
                      ->whereRaw("FIND_IN_SET('baptism', occasion)")
                      ->whereRaw("FIND_IN_SET('corporate', occasion)");
                });
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $inksQuery->where(function($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                  ->orWhere('ink_color', 'like', "%{$search}%")
                  ->orWhere('material_type', 'like', "%{$search}%");
            });
        }
        $inks = $inksQuery->get();

        $materialLowCount = $materials->filter(function($material) {
            $stock = optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0;
            $reorder = optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0;
            return $stock > 0 && $stock <= $reorder;
        })->count();

        $inkLowCount = $inks->filter(function($ink) {
            $stock = optional($ink->inventory)->stock_level ?? $ink->stock_qty ?? $ink->stock_qty_ml ?? 0;
            $reorder = optional($ink->inventory)->reorder_level ?? 10;
            return $stock > 0 && $stock <= $reorder;
        })->count();

        $materialOutCount = $materials->filter(function($material) {
            $stock = optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0;
            return $stock <= 0;
        })->count();

        $inkOutCount = $inks->filter(function($ink) {
            $stock = optional($ink->inventory)->stock_level ?? $ink->stock_qty ?? $ink->stock_qty_ml ?? 0;
            return $stock <= 0;
        })->count();

        $materialStockQty = $materials->sum(function($material) {
            return optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0;
        });

        $inkStockQty = $inks->sum(function($ink) {
            $stock = optional($ink->inventory)->stock_level;
            if ($stock !== null) {
                return $stock;
            }
            return $ink->stock_qty ?? $ink->stock_qty_ml ?? 0;
        });

        $summary = [
            'total_items' => $materials->count() + $inks->count(),
            'low_stock' => $materialLowCount + $inkLowCount,
            'out_stock' => $materialOutCount + $inkOutCount,
            'total_stock_qty' => $materialStockQty + $inkStockQty,
        ];

        return view('admin.materials.index', compact('materials', 'inks', 'summary'));
    }


    public function edit($id)
    {
        $material = \App\Models\Material::findOrFail($id);
        return view('admin.materials.edit', compact('material'));
    }

    public function update(Request $request, $id)
    {
        $material = Material::with('inventory')->findOrFail($id);

        $currentType = $request->input('material_type', $material->material_type);
        $request->merge(['material_type' => $currentType]);
        $isInk = $currentType === 'ink';

        if ($isInk) {
            $inputQty = $request->input('stock_qty');
            $fallbackQty = $material->stock_qty ?? optional($material->inventory)->stock_level ?? 0;
            if ($inputQty !== null) {
                $request->merge([
                    'stock_level' => $inputQty,
                    'stock_qty' => $inputQty,
                ]);
            } elseif ($request->filled('stock_level')) {
                $request->merge(['stock_qty' => $request->input('stock_level')]);
            } else {
                $request->merge([
                    'stock_level' => $fallbackQty,
                    'stock_qty' => $fallbackQty,
                ]);
            }

            if (!$request->filled('reorder_level')) {
                $request->merge([
                    'reorder_level' => optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0,
                ]);
            }

            if (!$request->filled('unit_cost') && $material->unit_cost !== null) {
                $request->merge(['unit_cost' => $material->unit_cost]);
            }
        }

        $rules = [
            'material_name' => [
                'required','string','max:255',
                // Ignore current id and scope uniqueness to material_type
                Rule::unique('materials', 'material_name')
                    ->where(function ($query) use ($request) {
                        return $query->where('material_type', $request->input('material_type'));
                    })
                    ->ignore($id, 'material_id'),
            ],
            'material_type' => 'nullable|string|max:50',  // ✅ Made nullable
            'unit'           => 'required|string|max:50',
            'unit_cost'      => $isInk ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'stock_level'    => 'required|integer|min:0',
            'reorder_level'  => 'required|integer|min:0',
            // removed remarks validation since it's automatic
        ];

        $rules['stock_qty'] = $isInk ? 'required|integer|min:0' : 'nullable|integer|min:0';

        $request->validate($rules);

        // ✅ Update Material (now includes stock fields)
        $previousStock = !$isInk
            ? (optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0)
            : ($material->stock_qty ?? optional($material->inventory)->stock_level ?? 0);

        $material->update([
            'material_name' => $request->material_name,
            'material_type' => $request->material_type,
            'unit'          => $request->unit,
            'unit_cost'     => $request->unit_cost ?? $material->unit_cost,
            'stock_qty'     => $request->stock_level,  // ✅ Added: Update stock_qty
            'reorder_point' => $request->reorder_level, // ✅ Added: Update reorder_point
            'date_updated'  => now(),
        ]);


        // ✅ Auto-generate remarks based on stock
        $stockLevel   = $request->stock_level;
        $reorderLevel = $request->reorder_level;
        $remarks = 'In Stock';

        if ($stockLevel <= 0) {
            $remarks = 'Out of Stock';
        } elseif ($stockLevel > 0 && $stockLevel <= $reorderLevel) {
            $remarks = 'Low Stock';
        }

        $inventoryData = [
            'stock_level'   => $stockLevel,
            'reorder_level' => $reorderLevel,
            'remarks'       => $remarks,
        ];

        if ($material->inventory) {
            $material->inventory->update($inventoryData);
        } else {
            $material->inventory()->create($inventoryData);
        }

        $material->load('inventory');
        $currentStock = optional($material->inventory)->stock_level;
        if ($currentStock === null) {
            $currentStock = $material->stock_qty ?? 0;
        }
        $stockDelta = $currentStock - $previousStock;

        if ($stockDelta > 0) {
            $owners = User::where('role', 'owner')->get();
            if ($owners->isNotEmpty()) {
                Notification::send($owners, new MaterialRestockedNotification(
                    $material,
                    $stockDelta,
                    Auth::user()
                ));
            }
        }

        if (in_array($remarks, ['Low Stock', 'Out of Stock'], true)) {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new StockNotification($material, $remarks));
            }
        }

        return redirect()->route('admin.materials.index')
                         ->with('success', 'Material updated successfully with inventory.');

    }

    public function destroy($id)
    {
        $material = \App\Models\Material::findOrFail($id);
        $material->delete();

        return redirect()->route('admin.materials.index')->with('success', 'Material deleted successfully.');
    }


    public function notification()
    {
        $lowStock = Material::with('inventory')
            ->whereHas('inventory', function($q) {
                $q->whereColumn('stock_level', '<=', 'reorder_level')
                  ->where('stock_level', '>', 0);
            })
            ->get();

        $outOfStock = Material::with('inventory')
            ->whereHas('inventory', function($q) {
                $q->where('stock_level', '<=', 0);
            })
            ->get();

        return view('admin.materials.notification', compact('lowStock', 'outOfStock'));
    }
}
/*public function notification()
{
    $lowStock = Material::with('inventory')
        ->whereHas('inventory', function($q) {
            $q->whereColumn('stock_level', '<=', 'reorder_level')
              ->where('stock_level', '>', 0);
        })
        ->get();

    $outOfStock = Material::with('inventory')
        ->whereHas('inventory', function($q) {
            $q->where('stock_level', '<=', 0);
        })
        ->get();

    

    return view('admin.materials.notification', compact('lowStock', 'outOfStock'));
}*/





