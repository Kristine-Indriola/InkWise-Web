<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            if (Schema::hasColumn('inventory', 'material_id')) {
                $table->dropForeign(['material_id']);
            }
        });

        Schema::table('inventory', function (Blueprint $table) {
            if (Schema::hasColumn('inventory', 'material_id')) {
                $table->unsignedBigInteger('material_id')->nullable()->change();
            }
            if (!Schema::hasColumn('inventory', 'ink_id')) {
                $table->unsignedBigInteger('ink_id')->nullable()->after('material_id');
            }
        });

        Schema::table('inventory', function (Blueprint $table) {
            if (Schema::hasColumn('inventory', 'material_id')) {
                $table->foreign('material_id')
                    ->references('material_id')
                    ->on('materials')
                    ->onDelete('cascade');
            }
            if (Schema::hasColumn('inventory', 'ink_id')) {
                $table->foreign('ink_id')
                    ->references('id')
                    ->on('inks')
                    ->onDelete('cascade');
            }
        });

        $inks = DB::table('inks')->select('id', 'stock_qty', 'stock_qty_ml')->get();

        foreach ($inks as $ink) {
            $stock = $ink->stock_qty ?? ($ink->stock_qty_ml ?? 0);
            $reorder = 10;
            $remarks = 'In Stock';

            if ($stock <= 0) {
                $remarks = 'Out of Stock';
            } elseif ($stock > 0 && $stock <= $reorder) {
                $remarks = 'Low Stock';
            }

            $exists = DB::table('inventory')->where('ink_id', $ink->id)->exists();

            $payload = [
                'material_id' => null,
                'ink_id' => $ink->id,
                'stock_level' => $stock,
                'reorder_level' => $reorder,
                'remarks' => $remarks,
                'updated_at' => now(),
            ];

            if ($exists) {
                DB::table('inventory')->where('ink_id', $ink->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('inventory')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory', 'ink_id')) {
            DB::table('inventory')->whereNotNull('ink_id')->delete();

            Schema::table('inventory', function (Blueprint $table) {
                $table->dropForeign(['ink_id']);
                $table->dropColumn('ink_id');
            });
        }

        if (Schema::hasColumn('inventory', 'material_id')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->dropForeign(['material_id']);
            });

            Schema::table('inventory', function (Blueprint $table) {
                $table->unsignedBigInteger('material_id')->nullable(false)->change();
                $table->foreign('material_id')
                    ->references('material_id')
                    ->on('materials')
                    ->onDelete('cascade');
            });
        }
    }
};
