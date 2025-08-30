<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Material;
use App\Models\Inventory;
use Illuminate\Http\Request;

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

        return redirect()->route('admin.materials.index')->with('success', 'Material added successfully with inventory!');
    }

    public function index()
{
    // Get all materials with their inventory (stock + reorder level)
    $materials = \App\Models\Material::with('inventory')->get();

    return view('admin.materials.index', compact('materials'));
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
        'material_type' => 'required|string|max:100',
        'unit' => 'required|string|max:50',
        'unit_cost' => 'required|numeric|min:0',
    ]);

    $material = \App\Models\Material::findOrFail($id);
    $material->update($request->all());

    return redirect()->route('admin.materials.index')->with('success', 'Material updated successfully.');
}

public function destroy($id)
{
    $material = \App\Models\Material::findOrFail($id);
    $material->delete();

    return redirect()->route('admin.materials.index')->with('success', 'Material deleted successfully.');
}
}
