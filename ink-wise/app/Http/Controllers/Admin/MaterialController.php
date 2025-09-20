<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;

use App\Models\Material;
use App\Models\Inventory;
use App\Models\Ink; // <-- Add this import
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\StockNotification;
use Illuminate\Support\Facades\Notification;

class MaterialController extends Controller
{
    public function create()
    {
        return view('admin.materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'string|in:wedding,birthday,baptism,corporate',
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

        foreach ($validated['occasion'] as $occasion) {
            $material = Material::create([
                'material_name' => $validated['material_name'],
                'occasion' => $occasion,
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
        }

        return redirect()->route('admin.materials.index')->with('success', 'Materials added successfully!');
    }

    public function index(Request $request)
    {
        $query = Material::with('inventory');

        if ($request->status === 'low') {
            $query->whereHas('inventory', function($q) {
                $q->whereColumn('stock_level', '<=', 'reorder_level')
                  ->where('stock_level', '>', 0);
            });
        }

        if ($request->status === 'out') {
            $query->whereHas('inventory', function($q) {
                $q->where('stock_level', '<=', 0);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                  ->orWhere('material_type', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        $materials = $query->get();
        $inks = \App\Models\Ink::all();

        // ✅ Added: Filter inks by search
        if ($request->filled('search')) {
            $search = $request->search;
            $inks = $inks->filter(function($ink) use ($search) {
                return stripos($ink->material_name, $search) !== false ||
                       stripos($ink->ink_color, $search) !== false ||
                       stripos($ink->material_type, $search) !== false;
            });
        }

        return view('admin.materials.index', compact('materials', 'inks'));
    }


    public function edit($id)
    {
        $material = \App\Models\Material::findOrFail($id);
        return view('admin.materials.edit', compact('material'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'material_name' => 'required|string|max:255',
            'material_type' => 'nullable|string|max:50',  // ✅ Made nullable
            'unit'           => 'required|string|max:50',
            'unit_cost'      => 'required|numeric|min:0',
            'stock_level'    => 'required|integer|min:0',
            'reorder_level'  => 'required|integer|min:0',
            // removed remarks validation since it's automatic
        ]);

        // ✅ Update Material (now includes stock fields)
        $material = Material::findOrFail($id);
        $material->update([
            'material_name' => $request->material_name,
            'material_type' => $request->material_type,
            'unit'          => $request->unit,
            'unit_cost'     => $request->unit_cost,
            'stock_qty'     => $request->stock_level,  // ✅ Added: Update stock_qty
            'reorder_point' => $request->reorder_level, // ✅ Added: Update reorder_point
            'volume_ml'     => $request->stock_level,   // ✅ Added: Update volume_ml (for ink types)
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

        // ✅ Update or create Inventory (only for non-ink materials)
        if ($request->material_type !== 'ink') {
            if ($material->inventory) {
                $material->inventory->update([
                    'stock_level'   => $stockLevel,
                    'reorder_level' => $reorderLevel,
                    'remarks'       => $remarks,
                ]);
            } else {
                $material->inventory()->create([
                    'stock_level'   => $stockLevel,
                    'reorder_level' => $reorderLevel,
                    'remarks'       => $remarks,
                ]);
            }
        }

        return redirect()->route('admin.materials.index')
                         ->with('success', 'Material updated successfully with inventory.');
    }

     if ($remarks === 'Low Stock' || $remarks === 'Out of Stock') {
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new StockNotification($material, $remarks));
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




}
