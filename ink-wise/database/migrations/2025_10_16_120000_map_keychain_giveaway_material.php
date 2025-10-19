I<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $material = DB::table('materials')
                ->select('material_id', 'material_name', 'material_type', 'color', 'unit', 'weight_gsm')
                ->whereRaw('LOWER(material_name) = ?', ['keychains (acrylic)'])
                ->first();

            if (!$material) {
                return;
            }

            $products = DB::table('products')
                ->select('id', 'name')
                ->whereIn(DB::raw('LOWER(name)'), ['keychains (acrylic)', 'elegant wedding keychain'])
                ->whereRaw('LOWER(product_type) = ?', ['giveaway'])
                ->get();

            foreach ($products as $product) {
                DB::table('product_materials')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'material_id' => $material->material_id,
                        'order_id' => null,
                        'source_type' => 'product',
                    ],
                    [
                        'item' => $material->material_name,
                        'type' => $material->material_type,
                        'color' => $material->color,
                        'unit' => $material->unit,
                        'weight' => $material->weight_gsm,
                        'qty' => 1.0,
                        'quantity_mode' => 'per_unit',
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ]
                );
            }

            if ($products->isNotEmpty()) {
                DB::table('product_addons')
                    ->whereIn('product_id', $products->pluck('id'))
                    ->whereNull('material_id')
                    ->where(function ($query) {
                        $query->whereRaw('LOWER(name) = ?', ['keychains (acrylic)'])
                              ->orWhereRaw('LOWER(name) = ?', ['elegant wedding keychain']);
                    })
                    ->update(['material_id' => $material->material_id]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $materialId = DB::table('materials')
                ->whereRaw('LOWER(material_name) = ?', ['keychains (acrylic)'])
                ->value('material_id');

            if (!$materialId) {
                return;
            }

            $productIds = DB::table('products')
                ->whereIn(DB::raw('LOWER(name)'), ['keychains (acrylic)', 'elegant wedding keychain'])
                ->whereRaw('LOWER(product_type) = ?', ['giveaway'])
                ->pluck('id');

            if ($productIds->isEmpty()) {
                return;
            }

            DB::table('product_materials')
                ->whereNull('order_id')
                ->whereIn('product_id', $productIds)
                ->where('material_id', $materialId)
                ->update([
                    'material_id' => null,
                    'item' => null,
                    'type' => null,
                    'color' => null,
                    'unit' => null,
                    'weight' => null,
                    'qty' => 0,
                ]);

            DB::table('product_addons')
                ->whereIn('product_id', $productIds)
                ->where('material_id', $materialId)
                ->update(['material_id' => null]);
        });
    }
};
