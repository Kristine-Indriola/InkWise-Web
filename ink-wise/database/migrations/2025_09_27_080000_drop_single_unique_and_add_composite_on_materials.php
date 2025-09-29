<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The composite unique index was already added in a previous migration
        // No need to drop anything or add again
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

            try {
                // restore the original single-column unique (if desired)
                $table->unique('material_name', 'materials_material_name_unique');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
