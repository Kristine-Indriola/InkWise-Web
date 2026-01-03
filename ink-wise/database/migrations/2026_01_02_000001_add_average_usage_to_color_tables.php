<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            if (!Schema::hasColumn('product_colors', 'average_usage_ml')) {
                $table->decimal('average_usage_ml', 10, 2)->nullable()->after('color_code');
            }
        });

        Schema::table('order_item_colors', function (Blueprint $table) {
            if (!Schema::hasColumn('order_item_colors', 'average_usage_ml')) {
                $table->decimal('average_usage_ml', 10, 2)->nullable()->after('color_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_item_colors', function (Blueprint $table) {
            if (Schema::hasColumn('order_item_colors', 'average_usage_ml')) {
                $table->dropColumn('average_usage_ml');
            }
        });

        Schema::table('product_colors', function (Blueprint $table) {
            if (Schema::hasColumn('product_colors', 'average_usage_ml')) {
                $table->dropColumn('average_usage_ml');
            }
        });
    }
};
