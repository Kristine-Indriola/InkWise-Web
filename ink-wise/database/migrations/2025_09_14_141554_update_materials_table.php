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
    Schema::table('materials', function (Blueprint $table) {
        // New identifiers
        $table->string('sku')->unique()->nullable()->after('material_id');

        // Categories
        $table->enum('occasion', ['wedding', 'birthday', 'baptism', 'corporate'])->after('material_name');
        $table->enum('product_type', ['invitation', 'giveaway'])->after('occasion');

        // Sub classification
        $table->string('material_type')->nullable()->change();  // ✅ Changed to string for free text



        // Attributes
        $table->string('size')->nullable()->after('material_type');      // A7, 5x7, roll size
        $table->string('color')->nullable()->after('size');             // White, Gold, Transparent
        $table->integer('weight_gsm')->nullable()->after('color');      // For cardstock
        $table->decimal('volume_ml', 8, 2)->nullable()->after('weight_gsm'); // For inks only

        
        // Stock management
        $table->integer('stock_qty')->default(0)->after('unit');
        $table->integer('reorder_point')->default(10)->after('stock_qty');
        $table->text('description')->nullable()->after('unit_cost');
    });
}

public function down(): void
{
    Schema::table('materials', function (Blueprint $table) {
        $table->dropColumn([
            'sku',
            'occasion',
            'product_type',
            'material_type',
            'size',
            'color',
            'weight_gsm',
            'volume_ml',
            'stock_qty',
            'reorder_point',
            'description'
        ]);
    });
}

    /**
     * Reverse the migrations.
     */
    
};

