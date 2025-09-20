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
         Schema::create('inventory', function (Blueprint $table) {
            $table->id('inventory_id');
            
            // Foreign key to materials
            $table->unsignedBigInteger('material_id');
            $table->integer('stock_level')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->string('remarks')->nullable();
            
            $table->timestamps();

            // FK Constraint
            $table->foreign('material_id')
                  ->references('material_id')->on('materials')
                  ->onDelete('cascade'); 
        });
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
