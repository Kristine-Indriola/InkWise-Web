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
        if (Schema::hasTable('order_item_sizes')) {
            return;
        }

        Schema::create('order_item_sizes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('product_sizes')->nullOnDelete();
            $table->string('size_type')->nullable();
            $table->string('size');
            $table->decimal('size_price', 12, 2)->nullable();
            $table->json('pricing_metadata')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('pricing_mode', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_sizes');
    }
};
