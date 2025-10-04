<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Material;

class StaffInventoryController extends Controller
{
    public function index()
    {
        // Sample inventory data for staff (invitations and giveaways)
        $inventories = collect([
            (object) [
                'id' => 1,
                'inventory_id' => 'INV001',
                'material' => (object) ['material_name' => 'Wedding Invitations'],
                'stock_level' => 150,
                'reorder_level' => 50,
                'pending_orders' => 12,
                'completed_orders' => 45,
                'cancelled_orders' => 3,
                'location' => 'Storage Room A',
                'updated_at' => now(),
            ],
            (object) [
                'id' => 2,
                'inventory_id' => 'INV002',
                'material' => (object) ['material_name' => 'Birthday Party Giveaways'],
                'stock_level' => 25,
                'reorder_level' => 30,
                'pending_orders' => 8,
                'completed_orders' => 22,
                'cancelled_orders' => 1,
                'location' => 'Storage Room B',
                'updated_at' => now()->subDays(2),
            ],
            (object) [
                'id' => 3,
                'inventory_id' => 'INV003',
                'material' => (object) ['material_name' => 'Corporate Event Invitations'],
                'stock_level' => 0,
                'reorder_level' => 20,
                'pending_orders' => 5,
                'completed_orders' => 18,
                'cancelled_orders' => 2,
                'location' => 'Storage Room A',
                'updated_at' => now()->subDays(5),
            ],
            (object) [
                'id' => 4,
                'inventory_id' => 'INV004',
                'material' => (object) ['material_name' => 'Holiday Giveaways'],
                'stock_level' => 75,
                'reorder_level' => 40,
                'pending_orders' => 15,
                'completed_orders' => 67,
                'cancelled_orders' => 4,
                'location' => 'Storage Room C',
                'updated_at' => now()->subDays(1),
            ],
        ]);
        return view('staff.inventory.index', compact('inventories'));
    }

    public function show($id)
    {
        // Sample inventory data for staff (invitations and giveaways)
        $sampleInventories = [
            1 => (object) [
                'id' => 1,
                'inventory_id' => 'INV001',
                'material' => (object) ['material_name' => 'Wedding Invitations'],
                'stock_level' => 150,
                'reorder_level' => 50,
                'pending_orders' => 12,
                'completed_orders' => 45,
                'cancelled_orders' => 3,
                'location' => 'Storage Room A',
                'updated_at' => now(),
                'remarks' => 'Elegant wedding invitation cards with premium paper quality.',
            ],
            2 => (object) [
                'id' => 2,
                'inventory_id' => 'INV002',
                'material' => (object) ['material_name' => 'Birthday Party Giveaways'],
                'stock_level' => 25,
                'reorder_level' => 30,
                'pending_orders' => 8,
                'completed_orders' => 22,
                'cancelled_orders' => 1,
                'location' => 'Storage Room B',
                'updated_at' => now()->subDays(2),
                'remarks' => 'Fun party favors and giveaways for birthday celebrations.',
            ],
            3 => (object) [
                'id' => 3,
                'inventory_id' => 'INV003',
                'material' => (object) ['material_name' => 'Corporate Event Invitations'],
                'stock_level' => 0,
                'reorder_level' => 20,
                'pending_orders' => 5,
                'completed_orders' => 18,
                'cancelled_orders' => 2,
                'location' => 'Storage Room A',
                'updated_at' => now()->subDays(5),
                'remarks' => 'Professional invitations for corporate events and conferences.',
            ],
            4 => (object) [
                'id' => 4,
                'inventory_id' => 'INV004',
                'material' => (object) ['material_name' => 'Holiday Giveaways'],
                'stock_level' => 75,
                'reorder_level' => 40,
                'pending_orders' => 15,
                'completed_orders' => 67,
                'cancelled_orders' => 4,
                'location' => 'Storage Room C',
                'updated_at' => now()->subDays(1),
                'remarks' => 'Seasonal holiday giveaways and promotional items.',
            ],
        ];

        $inventory = $sampleInventories[$id] ?? $sampleInventories[1];
        return view('staff.inventory.show', compact('inventory'));
    }

    public function create()
    {
        $materials = Material::all();
        return view('staff.inventory.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,material_id',
            'stock_level' => 'required|integer',
            'reorder_level' => 'required|integer',
            'remarks' => 'nullable|string'
        ]);

        Inventory::create($request->all());

        return redirect()->route('staff.inventory.index')
                         ->with('success', 'Inventory item added successfully.');
    }

    public function edit($id)
{
    $inventory = Inventory::findOrFail($id);
    $materials = Material::all();
    return view('staff.inventory.edit', compact('inventory', 'materials'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'material_id' => 'required|exists:materials,material_id',
        'stock_level' => 'required|integer',
        'reorder_level' => 'required|integer',
        'remarks' => 'nullable|string'
    ]);

    $inventory = Inventory::findOrFail($id);
    $inventory->update($request->all());

    return redirect()->route('staff.inventory.index')
                     ->with('success', 'Inventory updated successfully.');
}

public function destroy($id)
{
    $inventory = Inventory::findOrFail($id);
    $inventory->delete();

    return redirect()->route('staff.inventory.index')
                     ->with('success', 'Inventory deleted successfully.');
}

}
