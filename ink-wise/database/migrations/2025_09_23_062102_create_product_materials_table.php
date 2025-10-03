<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_materials')) {
            Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('material_id');
            $table->string('item')->nullable();
            
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->integer('weight')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};