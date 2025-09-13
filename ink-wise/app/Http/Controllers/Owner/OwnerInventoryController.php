<?php

namespace App\Http\Controllers\Owner;

use App\Models\Material;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OwnerInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('search');

        $materials = Material::with('inventory')
            ->when($query, function ($q) use ($query) {
                $q->where('material_name', 'like', "%$query%")
                  ->orWhere('material_type', 'like', "%$query%");
            })
            ->get();

        return view('owner.inventory-track', compact('materials')); 
        // Blade path: resources/views/owner/inventory-track.blade.php
    }

    public function track(Request $request)
    {
        $status = $request->input('status'); // Get status from query parameter
        $search = $request->input('search');

        $query = Material::with('inventory');

        // Filter by Low Stock if status=low
        if ($status === 'low') {
            $query->whereHas('inventory', function ($q) {
                $q->whereColumn('stock_level', '<=', 'reorder_level')
                ->where('stock_level', '>', 0);
            });
        }

        // Apply search if any
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                ->orWhere('material_type', 'like', "%{$search}%");
            });
        }

        $materials = $query->get();

        return view('owner.inventory-track', compact('materials', 'status', 'search'));
    }


}
