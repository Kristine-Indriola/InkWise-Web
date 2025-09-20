<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ink;
use Illuminate\Http\Request;

class InkController extends Controller
{
    public function create()
    {
        return view('admin.inks.create');
    }

    public function store(Request $request)
    {
        // ✅ Updated: Full validation for ink fields
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'string|in:wedding,birthday,baptism,corporate',
            'product_type' => 'required|string|in:invitation,giveaway',
            'ink_color' => 'required|string|max:50',
            'stock_qty_ml' => 'required|integer|min:0',
            'cost_per_ml' => 'required|numeric|min:0',
            'avg_usage_per_invite_ml' => 'nullable|numeric|min:0',
            'cost_per_invite' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if (is_array($validated['occasion'])) {
            foreach ($validated['occasion'] as $occasion) {
                $newData = $validated;
                $newData['occasion'] = $occasion;
                Ink::create($newData);  // ✅ Creates multiple rows
            }
        } else {
            Ink::create($validated);  // Fallback
        }

        return redirect()->route('admin.materials.index')->with('success', 'Inks added successfully.');
    }

    public function edit(Ink $ink)
    {
        return view('admin.inks.edit', compact('ink'));
    }

    public function update(Request $request, Ink $ink)
    {
        $ink->update($request->all());
        return redirect()->route('admin.materials.index')->with('success', 'Ink updated successfully.');
    }

    public function destroy(Ink $ink)
    {
        $ink->delete();
        return redirect()->route('admin.materials.index')->with('success', 'Ink deleted successfully.');
    }
}
