<?php

namespace App\Http\Controllers\Owner;

use App\Models\Material;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OwnerInventoryController extends Controller
{
    public function index()
{
    $materials = Material::with('inventory')->get();
    return view('owner.inventory-track', compact('materials')); 
    // Blade path must be: resources/views/owner/inventory-track.blade.php
}

  public function track(Request $request)
{
    $materials = Material::with('inventory')->get();

    if ($request->status === 'low') {
        $materials = $materials->filter(function ($m) {
            $stock = $m->inventory->stock_level ?? 0;
            $reorder = $m->inventory->reorder_level ?? 0;
            return $stock > 0 && $stock <= $reorder;
        });
    }

    if ($request->status === 'out') {
        $materials = $materials->filter(function ($m) {
            $stock = $m->inventory->stock_level ?? 0;
            return $stock == 0;
        });
    }

    return view('owner.inventory.track', compact('materials'));
}

    public function searchMaterials(Request $request)
        {
            // Get the search query from the request
            $query = $request->input('search');
            
            $materials = Material::where('material_name', 'like', "%$query%")
                                ->orWhere('material_type', 'like', "%$query%")
                                ->get();

            return view('owner.materials.index', compact('materials'));
        }

}
