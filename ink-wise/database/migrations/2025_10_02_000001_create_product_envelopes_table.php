<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_envelopes');
        Schema::create('product_envelopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('material_id')->nullable()->constrained('materials', 'material_id');
            $table->string('envelope_material_name')->nullable();
            $table->integer('max_qty')->nullable();
            $table->integer('max_quantity')->nullable();
            $table->decimal('price_per_unit', 10, 2)->nullable();
            $table->string('envelope_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_envelopes');
    }
};