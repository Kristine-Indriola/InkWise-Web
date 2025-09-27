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
        Schema::table('materials', function (Blueprint $table) {
            $table->string('material_type')->nullable()->change();  // ✅ Change to string
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Revert if needed (e.g., back to enum)
            $table->enum('material_type', ['cardstock', 'envelope', 'ink', 'foil', 'lamination', 'packaging'])->nullable()->change();
        });
    }
};
