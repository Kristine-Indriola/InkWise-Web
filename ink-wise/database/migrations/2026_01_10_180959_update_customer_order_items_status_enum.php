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
        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'processed', 'pre_order'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'processed'])->default('pending')->change();
        });
    }
};
