<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inks', function (Blueprint $table) {
            if (!Schema::hasColumn('inks', 'size')) {
                $table->string('size')->nullable()->after('material_type');
            }
            if (!Schema::hasColumn('inks', 'unit')) {
                $table->string('unit')->nullable()->after('size');
            }
            if (!Schema::hasColumn('inks', 'stock_qty')) {
                $table->integer('stock_qty')->default(0)->after('stock_qty_ml');
            }
            // Make existing stock_qty_ml nullable to avoid insert errors when not used
            $table->integer('stock_qty_ml')->nullable()->change();
            $table->decimal('avg_usage_per_invite_ml', 8, 2)->nullable()->change();
            $table->decimal('cost_per_invite', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inks', function (Blueprint $table) {
            if (Schema::hasColumn('inks', 'size')) {
                $table->dropColumn('size');
            }
            if (Schema::hasColumn('inks', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('inks', 'stock_qty')) {
                $table->dropColumn('stock_qty');
            }
            // Revert nullable changes (may fail if NULL values exist)
            $table->integer('stock_qty_ml')->change();
            $table->decimal('avg_usage_per_invite_ml', 8, 2)->change();
            $table->decimal('cost_per_invite', 8, 2)->change();
        });
    }
};
