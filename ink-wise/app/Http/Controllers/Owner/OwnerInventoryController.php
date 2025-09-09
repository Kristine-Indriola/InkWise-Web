<?php

namespace App\Http\Controllers\Owner;

use App\Models\Material;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;

class OwnerInventoryController extends Controller
{
    public function index()
    {
        $materials = \App\Models\Material::with('inventory')->get();
        return view('owner.inventory-track', compact('materials'));
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

    return view('owner.inventory.track', compact('materials'));
}

}
