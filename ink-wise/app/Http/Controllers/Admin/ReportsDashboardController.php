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

        $materialLabels = $materials->pluck('material_name');
        $materialStockLevels = $materials->map(fn ($material) => optional($material->inventory)->stock_level ?? 0);
        $materialReorderLevels = $materials->map(fn ($material) => optional($material->inventory)->reorder_level ?? 0);

        // TODO: replace placeholders with real sales aggregation once orders are available
        $sales = collect();
        $monthlyLabels = [];
        $monthlyTotals = [];

        return view('admin.reports.index', compact(
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
