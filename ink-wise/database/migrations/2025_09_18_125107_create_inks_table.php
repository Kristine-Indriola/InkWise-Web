<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inks', function (Blueprint $table) {
            $table->id(); // ID
            $table->string('material_name'); // Material Name
            $table->string('occasion')->nullable(); // Occasion
            $table->string('product_type'); // Product Type
            $table->string('ink_color'); // Ink Color
            $table->string('material_type')->nullable(); // Material Type
            $table->integer('stock_qty_ml'); // Stock Qty (ml)
            $table->decimal('cost_per_ml', 8, 2); // Cost per ml (₱)
            $table->decimal('avg_usage_per_invite_ml', 8, 2); // Average Usage per Invite (ml)
            $table->decimal('cost_per_invite', 8, 2); // Cost per Invite (₱)
            $table->text('description')->nullable(); // Description
            $table->timestamps(); // date_added & date_updated
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('inks');
    }
};

