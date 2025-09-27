<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_inks')) {
            Schema::create('product_inks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('ink_id');
            $table->string('item')->nullable();
            $table->string('type')->nullable();
            $table->decimal('usage', 10, 2)->nullable();
            $table->decimal('cost_per_ml', 10, 2)->nullable();
            
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('ink_id')->references('id')->on('inks')->onDelete('cascade');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inks');
    }
};