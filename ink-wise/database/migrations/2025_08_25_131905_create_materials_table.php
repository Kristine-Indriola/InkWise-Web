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
        // Materials Table
        Schema::create('materials', function (Blueprint $table) {
            $table->id('material_id');
            $table->string('material_name');
            $table->string('material_type')->nullable();
            $table->string('unit')->default('pcs'); // e.g. pcs, box, kg
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamp('date_added')->useCurrent();
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
