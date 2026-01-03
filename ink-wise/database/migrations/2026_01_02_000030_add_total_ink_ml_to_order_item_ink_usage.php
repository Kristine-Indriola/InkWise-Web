<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_item_ink_usage') && !Schema::hasColumn('order_item_ink_usage', 'total_ink_ml')) {
            Schema::table('order_item_ink_usage', function (Blueprint $table) {
                $table->decimal('total_ink_ml', 12, 2)->nullable()->after('average_usage_ml');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_item_ink_usage') && Schema::hasColumn('order_item_ink_usage', 'total_ink_ml')) {
            Schema::table('order_item_ink_usage', function (Blueprint $table) {
                $table->dropColumn('total_ink_ml');
            });
        }
    }
};
