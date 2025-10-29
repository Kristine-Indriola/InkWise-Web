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
            if (!Schema::hasColumn('templates', 'svg_path')) {
                $table->string('svg_path')->nullable()->after('preview');
            }
            if (!Schema::hasColumn('templates', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('svg_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'svg_path')) {
                $table->dropColumn('svg_path');
            }
            if (Schema::hasColumn('templates', 'processed_at')) {
                $table->dropColumn('processed_at');
            }
        });
    }
};
