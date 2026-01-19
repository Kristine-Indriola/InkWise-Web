<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductMaterial;
use App\Services\OrderFlowService;
use App\Support\Admin\OrderSummaryPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderSummaryController extends Controller
{
    public function show(Request $request, Order $order = null)
    {
        $orderModel = $order ?? Order::query()->latest('order_date')->latest()->first();

        if (! $orderModel) {
            return view('admin.ordersummary.index', ['order' => null]);
        }

        // Load activities for timeline
        $orderModel->loadMissing(['activities' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        $presented = OrderSummaryPresenter::make($orderModel);

        return view('admin.ordersummary.index', [
            'order' => $presented,
            'orderModel' => $orderModel,
        ]);
    }

    public function deductMaterials(Request $request, Order $order)
    {
        // Only allow admins to force material deduction
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            DB::beginTransaction();

            // Force material deduction regardless of payment status
            $orderFlowService = app(OrderFlowService::class);
            $orderFlowService->syncMaterialUsage($order, true);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Materials have been deducted from inventory.',
                'deducted_materials' => ProductMaterial::where('order_id', $order->id)
                    ->where('source_type', 'custom')
                    ->with('material')
                    ->get()
                    ->map(function ($pm) {
                        return [
                            'material_name' => $pm->material->material_name ?? 'Unknown',
                            'quantity_used' => $pm->quantity_used,
                            'unit' => $pm->unit,
                        ];
                    })
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to deduct materials: ' . $e->getMessage()
            ], 500);
        }
    }
}
