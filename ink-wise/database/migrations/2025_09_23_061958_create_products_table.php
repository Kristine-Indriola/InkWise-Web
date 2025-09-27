<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
            $table->string('name');
            $table->string('event_type');
            $table->string('product_type');
            $table->string('theme_style')->nullable();
            $table->text('description')->nullable();
            $table->string('color_options')->nullable();
            $table->string('envelope_options')->nullable();
            $table->integer('min_order_qty')->nullable();
            $table->string('bulk_pricing')->nullable();
            $table->string('lead_time')->nullable();
            $table->string('stock_availability')->nullable();
            $table->decimal('total_raw_cost', 10, 2)->nullable();
            $table->integer('quantity_ordered')->nullable();
            $table->decimal('cost_per_invite', 10, 2)->nullable();
            $table->integer('markup')->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('total_selling_price', 10, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};