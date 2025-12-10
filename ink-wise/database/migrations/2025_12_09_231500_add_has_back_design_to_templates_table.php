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
        Schema::table('templates', function (Blueprint $table) {
            if (!Schema::hasColumn('templates', 'has_back_design')) {
                // Place after figma_synced_at when present, otherwise keep near svg_path
                $afterColumn = Schema::hasColumn('templates', 'figma_synced_at') ? 'figma_synced_at' : 'processed_at';
                $table->boolean('has_back_design')->default(false)->after($afterColumn);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'has_back_design')) {
                $table->dropColumn('has_back_design');
            }
        });
    }
};
