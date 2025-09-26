<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Add a unique index on material_name to prevent duplicate ink entries
        if (!Schema::hasTable('inks')) return;

        // Guard against existing duplicate values — migration will fail if duplicates exist.
        // It's recommended to run a pre-check: SELECT material_name, COUNT(*) c FROM inks GROUP BY material_name HAVING c > 1;

        Schema::table('inks', function (Blueprint $table) {
            // Attempt to add the unique index. If the index already exists or DB driver
            // doesn't support introspection, catch the exception and proceed.
            try {
                $table->unique('material_name', 'material_name_unique');
            } catch (\Throwable $e) {
                // ignore — index likely exists or driver doesn't allow creation here
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inks')) return;
        Schema::table('inks', function (Blueprint $table) {
            try {
                $table->dropUnique('material_name_unique');
            } catch (\Throwable $e) {
                // ignore if index does not exist
            }
        });
    }
};
