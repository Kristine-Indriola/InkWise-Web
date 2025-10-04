<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('order_items', 'line_type')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('line_type', 32)->default('invitation')->after('product_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'line_type')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('line_type');
            });
        }
    }
};
