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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'lead_time')) {
                $table->string('lead_time')->nullable()->after('base_price');
            }
            if (!Schema::hasColumn('products', 'date_available')) {
                $table->date('date_available')->nullable()->after('lead_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'date_available')) {
                $table->dropColumn('date_available');
            }
            if (Schema::hasColumn('products', 'lead_time')) {
                $table->dropColumn('lead_time');
            }
        });
    }
};