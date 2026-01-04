<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ink_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ink_id');
            $table->enum('movement_type', ['restock', 'usage', 'adjustment']);
            $table->integer('quantity');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('ink_id')->references('id')->on('inks')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ink_stock_movements');
    }
};
