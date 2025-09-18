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
        Schema::create('inks', function (Blueprint $table) {
    $table->id();
    $table->string('material_name');
    $table->string('occasion')->nullable();
    $table->string('product_type')->nullable();
    $table->string('ink_color');
    $table->string('type'); // Standard, Metallic, Fluorescent
    $table->integer('stock_qty_ml')->default(0);
    $table->decimal('cost_per_ml', 10, 2)->default(0.00);
    $table->decimal('avg_usage_per_invite_ml', 10, 2)->default(0.00);
    $table->decimal('cost_per_invite', 10, 2)->default(0.00);
    $table->text('description')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inks');
    }
};
