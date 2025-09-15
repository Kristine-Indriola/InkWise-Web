<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    // ...existing code...

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku'           => 'nullable|string|unique:materials,sku',
            'material_name' => 'required|string|max:255',
            'occasion'      => 'required|in:wedding,birthday,baptism,corporate',
            'product_type'  => 'required|in:invitation,giveaway',
            'material_type' => 'required|in:cardstock,envelope,ink,foil,lamination,packaging',
            'size'          => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:100',
            'weight_gsm'    => 'nullable|integer',
            'volume_ml'     => 'nullable|numeric',
            'unit'          => 'required|string|max:50',
            'unit_cost'     => 'required|numeric|min:0',
            'stock_qty'     => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'description'   => 'nullable|string',
        ]);

        \App\Models\Material::create($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material added successfully!');
    }

    public function update(Request $request, $id)
    {
        $material = \App\Models\Material::findOrFail($id);

        $validated = $request->validate([
            'sku'           => 'nullable|string|unique:materials,sku,' . $material->material_id . ',material_id',
            'material_name' => 'required|string|max:255',
            'occasion'      => 'required|in:wedding,birthday,baptism,corporate',
            'product_type'  => 'required|in:invitation,giveaway',
            'material_type' => 'required|in:cardstock,envelope,ink,foil,lamination,packaging',
            'size'          => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:100',
            'weight_gsm'    => 'nullable|integer',
            'volume_ml'     => 'nullable|numeric',
            'unit'          => 'required|string|max:50',
            'unit_cost'     => 'required|numeric|min:0',
            'stock_qty'     => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'description'   => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material updated successfully!');
    }

    // ...existing code...
}