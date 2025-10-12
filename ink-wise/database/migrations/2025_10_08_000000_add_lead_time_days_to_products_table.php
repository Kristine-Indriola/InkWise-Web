<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        if (!Schema::hasColumn('products', 'lead_time_days')) {
            Schema::table('products', function (Blueprint $table) {
                // place after 'lead_time' if it exists, otherwise just add
                if (Schema::hasColumn('products', 'lead_time')) {
                    $table->unsignedSmallInteger('lead_time_days')->nullable()->after('lead_time');
                } else {
                    $table->unsignedSmallInteger('lead_time_days')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'lead_time_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('lead_time_days');
            });
        }
    }
};
