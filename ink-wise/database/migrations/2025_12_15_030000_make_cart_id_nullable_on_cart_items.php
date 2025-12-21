<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'cart_id')) {
            // Attempt to drop FK if exists
            try {
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->dropForeign(['cart_id']);
                });
            } catch (\Throwable $_e) {
                // ignore
            }

            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('cart_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'cart_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('cart_id')->nullable(false)->change();
            });
        }
    }
};
