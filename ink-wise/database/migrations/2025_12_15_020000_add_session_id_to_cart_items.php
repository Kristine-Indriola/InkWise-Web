<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'session_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('session_id')->nullable()->after('customer_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'session_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }
    }
};
