<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class ReportsDashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- Inventory Data ---
        $materials = Material::with('inventory')->get();

        // Labels for chart
        $materialLabels = $materials->pluck('material_name');

        // Stock and reorder levels for chart
        $materialStockLevels = $materials->map(fn($m) => $m->inventory->stock_level ?? 0);
        $materialReorderLevels = $materials->map(fn($m) => $m->inventory->reorder_level ?? 0);

        // Pass empty sales-related variables to prevent blade errors
        $sales = collect();             // empty collection
        $monthlyLabels = [];            // empty array
        $monthlyTotals = [];            // empty array

        return view('admin.reports.reports', compact(
            'materials',
            'materialLabels',
            'materialStockLevels',
            'materialReorderLevels',
            'sales',
            'monthlyLabels',
            'monthlyTotals'
        ));
    }
}
