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
        Schema::create('materials', function (Blueprint $table) {
    $table->id();
    $table->string('material_name');
    $table->string('occasion')->nullable();
    $table->string('product_type')->nullable();
    $table->string('type'); // Cardstock, Envelope, Giveaway, Printing Material
    $table->string('size')->nullable();
    $table->string('color')->nullable();
    $table->string('weight_gsm')->nullable();
    $table->string('unit');
    $table->integer('stock_qty')->default(0);
    $table->integer('reorder_point')->default(0);
    $table->text('description')->nullable();
    $table->enum('status', ['active','inactive'])->default('active');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
