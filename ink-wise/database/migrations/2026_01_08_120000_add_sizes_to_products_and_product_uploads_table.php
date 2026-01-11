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
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'sizes')) {
                    $table->json('sizes')->nullable()->after('description');
                }
            });
        }

        if (Schema::hasTable('product_uploads')) {
            Schema::table('product_uploads', function (Blueprint $table) {
                if (!Schema::hasColumn('product_uploads', 'sizes')) {
                    $table->json('sizes')->nullable()->after('design_data');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'sizes')) {
                    $table->dropColumn('sizes');
                }
            });
        }

        if (Schema::hasTable('product_uploads')) {
            Schema::table('product_uploads', function (Blueprint $table) {
                if (Schema::hasColumn('product_uploads', 'sizes')) {
                    $table->dropColumn('sizes');
                }
            });
        }
    }
};
