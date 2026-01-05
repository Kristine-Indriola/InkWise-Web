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
            if (!Schema::hasColumn('templates', 'preview_front')) {
                $table->string('preview_front')->nullable()->after('preview');
            }

            if (!Schema::hasColumn('templates', 'preview_back')) {
                $table->string('preview_back')->nullable()->after('preview_front');
            }

            if (Schema::hasColumn('templates', 'svg_front_path')) {
                $table->dropColumn('svg_front_path');
            }

            if (Schema::hasColumn('templates', 'svg_back_path')) {
                $table->dropColumn('svg_back_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'preview_back')) {
                $table->dropColumn('preview_back');
            }

            if (Schema::hasColumn('templates', 'preview_front')) {
                $table->dropColumn('preview_front');
            }

            if (!Schema::hasColumn('templates', 'svg_front_path')) {
                $table->string('svg_front_path')->nullable()->after('svg_path');
            }

            if (!Schema::hasColumn('templates', 'svg_back_path')) {
                $table->string('svg_back_path')->nullable()->after('svg_front_path');
            }
        });
    }
};
