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
            // Add composite unique index on (material_name, material_type) so the
            // same material name can exist under different material types.
            try {
                $table->unique(['material_name', 'material_type'], 'materials_name_type_unique');
            } catch (\Throwable $e) {
                // ignore if index already exists or DB driver limitations
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            try {
                $table->dropUnique('materials_name_type_unique');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
