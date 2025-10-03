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
        Schema::table('products', function (Blueprint $table) {
            // Add new material fields (skip image as it already exists)
            $table->string('type')->nullable()->after('stock_availability');
            $table->string('item')->nullable()->after('type');
            $table->string('color')->nullable()->after('item');
            $table->string('size')->nullable()->after('color');
            $table->integer('weight')->nullable()->after('size');
            $table->decimal('unit_price', 10, 2)->nullable()->after('weight');

            // Remove unused columns
            $table->dropColumn([
                'color_options',
                'envelope_options',
                'bulk_pricing',
                'total_raw_cost',
                'quantity_ordered',
                'cost_per_invite',
                'markup',
                'selling_price',
                'total_selling_price',
                'status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove added columns (skip image as it's handled by another migration)
            $table->dropColumn([
                'type',
                'item',
                'color',
                'size',
                'weight',
                'unit_price'
            ]);

            // Restore removed columns
            $table->string('color_options')->nullable();
            $table->string('envelope_options')->nullable();
            $table->string('bulk_pricing')->nullable();
            $table->decimal('total_raw_cost', 10, 2)->nullable();
            $table->integer('quantity_ordered')->nullable();
            $table->decimal('cost_per_invite', 10, 2)->nullable();
            $table->integer('markup')->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('total_selling_price', 10, 2)->nullable();
            $table->string('status')->default('active');
        });
    }
};
