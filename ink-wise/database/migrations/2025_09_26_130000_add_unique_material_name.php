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
            // Attempt to add the unique index. If it already exists or the driver
            // does not support introspection, catch and ignore the exception.
            try {
                $table->unique('material_name', 'materials_material_name_unique');
            } catch (\Throwable $e) {
                // ignore
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
                $table->dropUnique('materials_material_name_unique');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
