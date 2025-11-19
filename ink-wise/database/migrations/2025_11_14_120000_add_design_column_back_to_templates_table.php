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
        if (!Schema::hasColumn('templates', 'metadata')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('templates', 'design')) {
            $afterColumn = Schema::hasColumn('templates', 'metadata') ? 'metadata' : 'status';

            Schema::table('templates', function (Blueprint $table) use ($afterColumn) {
                $table->longText('design')->nullable()->after($afterColumn);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'design')) {
                $table->dropColumn('design');
            }
        });
    }
};
