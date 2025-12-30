<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

use App\Models\Material;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\MaterialRestockedNotification;
use App\Notifications\StockNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StaffMaterialController extends Controller
{
    public function create()
    {
        return view('staff.materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'material_type' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric',
            'stock_level' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        // Save Material
        $material = Material::create([
            'material_name' => $validated['material_name'],
            'material_type' => $validated['material_type'],
            'unit' => $validated['unit'],
            'unit_cost' => $validated['unit_cost'],
            'date_added' => now(),
            'date_updated' => now(),
        ]);

        // Save Inventory (linked to material)
        Inventory::create([
            'material_id' => $material->material_id,
            'stock_level' => $validated['stock_level'],
            'reorder_level' => $validated['reorder_level'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()->route('staff.materials.index')->with('success', 'Material added successfully with inventory!');
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

    return view('staff.materials.index', compact('materials'));
}


public function edit($id)
{
    $material = \App\Models\Material::findOrFail($id);
    return view('staff.materials.edit', compact('material'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'material_name'  => 'required|string|max:255',
        'material_type'  => 'required|string|max:100',
        'unit'           => 'required|string|max:50',
        'unit_cost'      => 'required|numeric|min:0',
        'stock_level'    => 'required|integer|min:0',
        'reorder_level'  => 'required|integer|min:0',
        // removed remarks validation since it's automatic
    ]);

    // ✅ Update Material
    $material = Material::findOrFail($id);
    $material->update([
        'material_name' => $request->material_name,
        'material_type' => $request->material_type,
        'unit'          => $request->unit,
        'unit_cost'     => $request->unit_cost,
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

    // ✅ Update or create Inventory
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

    return redirect()->route('staff.materials.index')
                     ->with('success', 'Material updated successfully with inventory.');
}

public function restock(Request $request, $id)
{
    $material = Material::with('inventory')->findOrFail($id);

    $validated = $request->validate([
        'quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string|max:500',
    ]);

    $quantity = (int) $validated['quantity'];
    $notes = $validated['notes'] ?? null;

    DB::transaction(function () use ($material, $quantity, $notes) {
        $currentStock = optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0;
        $newStock = $currentStock + $quantity;

        $material->update([
            'stock_qty' => $newStock,
            'date_updated' => now(),
        ]);

        $reorderLevel = optional($material->inventory)->reorder_level ?? $material->reorder_point ?? 0;
        $remarks = 'In Stock';
        if ($newStock <= 0) {
            $remarks = 'Out of Stock';
        } elseif ($newStock <= $reorderLevel) {
            $remarks = 'Low Stock';
        }

        $inventoryPayload = [
            'stock_level' => $newStock,
            'reorder_level' => $reorderLevel,
            'remarks' => $remarks,
        ];

        if ($material->inventory) {
            $material->inventory->update($inventoryPayload);
        } else {
            $material->inventory()->create($inventoryPayload);
            $material->load('inventory');
        }

        StockMovement::create([
            'material_id' => $material->getKey(),
            'movement_type' => 'restock',
            'quantity' => $quantity,
            'user_id' => Auth::id(),
            'notes' => $notes,
        ]);
    });

    $material->refresh()->load('inventory');
    $remarks = optional($material->inventory)->remarks ?? 'In Stock';

    $owners = User::where('role', 'owner')->get();
    if ($owners->isNotEmpty()) {
        Notification::send($owners, new MaterialRestockedNotification($material, $quantity, Auth::user()));
    }

    $admins = User::where('role', 'admin')->get();
    if ($admins->isNotEmpty()) {
        Notification::send($admins, new MaterialRestockedNotification($material, $quantity, Auth::user()));
    }

    if (in_array($remarks, ['Low Stock', 'Out of Stock'], true) && $admins->isNotEmpty()) {
        Notification::send($admins, new StockNotification($material, $remarks));
    }

    $currentStock = optional($material->inventory)->stock_level ?? $material->stock_qty ?? 0;

    return redirect()->route('staff.materials.index')
        ->with('success', "Restocked {$material->material_name} by {$quantity} units. Current stock: {$currentStock}.");
}

public function destroy($id)
{
    $material = \App\Models\Material::findOrFail($id);
    $material->delete();

    return redirect()->route('staff.materials.index')->with('success', 'Material deleted successfully.');
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

    return view('staff.materials.notification', compact('lowStock', 'outOfStock'));
}



}
