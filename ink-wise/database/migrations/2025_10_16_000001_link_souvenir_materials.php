<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $materialLinks = [
                [
                    'product_name' => 'Mug',
                    'product_type' => 'Giveaway',
                    'material_name' => 'Mug',
                    'material_qty' => 1.0,
                ],
                [
                    'product_name' => 'Keychains (Acrylic)',
                    'product_type' => 'Giveaway',
                    'material_name' => 'Keychains (Acrylic)',
                    'material_qty' => 1.0,
                ],
            ];

            foreach ($materialLinks as $link) {
                $material = DB::table('materials')
                    ->select('material_id', 'material_name', 'material_type', 'color', 'unit', 'weight_gsm')
                    ->whereRaw('LOWER(material_name) = ?', [strtolower($link['material_name'])])
                    ->first();

                if (!$material) {
                    continue;
                }

                $product = DB::table('products')
                    ->select('id', 'name')
                    ->whereRaw('LOWER(name) = ?', [strtolower($link['product_name'])])
                    ->when(isset($link['product_type']), function ($query) use ($link) {
                        $query->whereRaw('LOWER(product_type) = ?', [strtolower($link['product_type'])]);
                    })
                    ->first();

                if ($product) {
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
                            'qty' => $link['material_qty'],
                            'quantity_mode' => 'per_unit',
                                'updated_at' => now(),
                                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                        ]
                    );
                }

                $addon = DB::table('product_addons')
                    ->whereRaw('LOWER(name) = ?', [strtolower($link['material_name'])])
                    ->first();

                if ($addon) {
                    DB::table('product_addons')
                        ->where('id', $addon->id)
                        ->update(['material_id' => $material->material_id]);
                }
            }

            $envelopeMaterial = DB::table('materials')
                ->select('material_id')
                ->whereRaw('LOWER(material_name) = ?', ['mattes'])
                ->first();

            if ($envelopeMaterial) {
                $envelopeProduct = DB::table('products')
                    ->select('id')
                    ->whereRaw('LOWER(name) = ?', ['mattes'])
                    ->whereRaw('LOWER(product_type) = ?', ['envelope'])
                    ->first();

                if ($envelopeProduct) {
                    DB::table('product_envelopes')
                        ->where('product_id', $envelopeProduct->id)
                        ->update(['material_id' => $envelopeMaterial->material_id]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $materialNames = ['mug', 'keychains (acrylic)', 'mattes'];

            $materialIds = DB::table('materials')
                ->where(function ($query) use ($materialNames) {
                    foreach ($materialNames as $index => $name) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $query->{$method}('LOWER(material_name) = ?', [$name]);
                    }
                })
                ->pluck('material_id');

            if ($materialIds->isNotEmpty()) {
                DB::table('product_materials')
                    ->whereNull('order_id')
                    ->whereIn('material_id', $materialIds)
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
                    ->whereIn('material_id', $materialIds)
                    ->update(['material_id' => null]);
            }

            $envelopeProduct = DB::table('products')
                ->select('id')
                ->whereRaw('LOWER(name) = ?', ['mattes'])
                ->whereRaw('LOWER(product_type) = ?', ['envelope'])
                ->first();

            if ($envelopeProduct) {
                DB::table('product_envelopes')
                    ->where('product_id', $envelopeProduct->id)
                    ->update(['material_id' => null]);
            }
        });
    }
};
