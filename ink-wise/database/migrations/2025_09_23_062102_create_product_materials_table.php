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

                $table->foreignId('customer_id')
                    ->nullable()
                    ->constrained('customers', 'customer_id')
                    ->nullOnDelete();

                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->foreignId('order_item_id')
                    ->nullable()
                    ->constrained('order_items')
                    ->cascadeOnDelete();

                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();

                $table->foreignId('material_id')
                    ->nullable()
                    ->constrained('materials', 'material_id')
                    ->nullOnDelete();

                $table->enum('source_type', ['product', 'paper_stock', 'envelope', 'addon', 'custom'])
                    ->default('product');
                $table->unsignedBigInteger('source_id')->nullable();

                $table->string('item')->nullable();
                $table->string('type')->nullable();
                $table->string('color')->nullable();
                $table->string('unit')->nullable();
                $table->integer('weight')->nullable();

                $table->decimal('qty', 12, 4)->default(0);

                $table->enum('quantity_mode', ['per_unit', 'per_order'])->default('per_unit');
                $table->unsignedInteger('order_quantity')->nullable();
                $table->decimal('quantity_required', 12, 4)->default(0);
                $table->decimal('quantity_reserved', 12, 4)->default(0);
                $table->decimal('quantity_used', 12, 4)->default(0);

                $table->timestamp('reserved_at')->nullable();
                $table->timestamp('deducted_at')->nullable();

                $table->json('metadata')->nullable();

                $table->timestamps();

                $table->index(['order_id', 'order_item_id']);
                $table->index(['product_id', 'material_id']);
                $table->index(['customer_id']);
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};