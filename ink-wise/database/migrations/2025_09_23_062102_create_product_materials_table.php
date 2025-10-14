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

                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('order_item_id')->nullable()->index();

                $table->string('item')->nullable();
                $table->string('type')->nullable();
                $table->string('color')->nullable();
                $table->integer('weight')->nullable();
                $table->decimal('unit_price', 10, 2)->nullable();
                $table->integer('qty')->nullable();
                $table->decimal('cost', 10, 2)->nullable();
                $table->decimal('quantity_used', 10, 2)->nullable();
                $table->timestamp('deducted_at')->nullable();

                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};