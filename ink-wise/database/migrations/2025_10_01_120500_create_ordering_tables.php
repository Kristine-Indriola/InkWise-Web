<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('additional_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->dateTime('order_date')->useCurrent();
            $table->enum('status', ['pending', 'confirmed', 'in_production', 'completed', 'cancelled'])->default('pending');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('date_needed')->nullable();
            $table->string('shipping_option')->default('standard');
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->json('summary_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('design_metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_bulk', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_bulk_order_id')->nullable()->constrained('product_bulk_orders')->nullOnDelete();
            $table->unsignedInteger('qty_selected')->nullable();
            $table->decimal('price_per_unit', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_paper_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('paper_stock_id')->nullable()->constrained('product_paper_stocks')->nullOnDelete();
            $table->string('paper_stock_name')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('addon_id')->nullable()->constrained('product_addons')->nullOnDelete();
            $table->string('addon_type')->nullable();
            $table->string('addon_name');
            $table->decimal('addon_price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('product_colors')->nullOnDelete();
            $table->string('color_name')->nullable();
            $table->string('color_code', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_colors');
        Schema::dropIfExists('order_item_addons');
        Schema::dropIfExists('order_item_paper_stock');
        Schema::dropIfExists('order_item_bulk');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customer_orders');
    }
};
