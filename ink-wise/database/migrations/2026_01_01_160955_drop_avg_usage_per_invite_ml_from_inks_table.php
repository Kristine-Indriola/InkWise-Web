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
        Schema::table('inks', function (Blueprint $table) {
            $table->dropColumn('avg_usage_per_invite_ml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inks', function (Blueprint $table) {
            $table->decimal('avg_usage_per_invite_ml', 8, 2)->nullable()->after('cost_per_ml');
        });
    }
};
