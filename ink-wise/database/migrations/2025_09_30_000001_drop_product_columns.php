<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToDrop = [
            'min_order_qty',
            'lead_time',
            'date_available',
            'stock_availability',
            'type',
            'item',
            'color',
            'size',
            'weight',
        ];

        // collect only existing columns to avoid errors
        $existing = [];
        foreach ($columnsToDrop as $col) {
            if (Schema::hasColumn('products', $col)) {
                $existing[] = $col;
            }
        }

        if (!empty($existing)) {
            Schema::table('products', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate columns only if they don't already exist
        if (!Schema::hasColumn('products', 'min_order_qty')
            || !Schema::hasColumn('products', 'lead_time')
            || !Schema::hasColumn('products', 'date_available')
            || !Schema::hasColumn('products', 'stock_availability')
            || !Schema::hasColumn('products', 'type')
            || !Schema::hasColumn('products', 'item')
            || !Schema::hasColumn('products', 'color')
            || !Schema::hasColumn('products', 'size')
            || !Schema::hasColumn('products', 'weight')) {

            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'min_order_qty')) {
                    $table->integer('min_order_qty')->nullable();
                }
                if (!Schema::hasColumn('products', 'lead_time')) {
                    $table->string('lead_time')->nullable();
                }
                if (!Schema::hasColumn('products', 'date_available')) {
                    $table->date('date_available')->nullable();
                }
                if (!Schema::hasColumn('products', 'stock_availability')) {
                    $table->string('stock_availability')->nullable();
                }
                if (!Schema::hasColumn('products', 'type')) {
                    $table->string('type')->nullable();
                }
                if (!Schema::hasColumn('products', 'item')) {
                    $table->string('item')->nullable();
                }
                if (!Schema::hasColumn('products', 'color')) {
                    $table->string('color')->nullable();
                }
                if (!Schema::hasColumn('products', 'size')) {
                    $table->string('size')->nullable();
                }
                if (!Schema::hasColumn('products', 'weight')) {
                    $table->integer('weight')->nullable();
                }
            });
        }
    }
};
