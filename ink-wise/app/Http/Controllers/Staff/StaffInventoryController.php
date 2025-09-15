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
        $inventories = Inventory::with('material')->get(); 
        return view('staff.inventory.index', compact('inventories'));
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
