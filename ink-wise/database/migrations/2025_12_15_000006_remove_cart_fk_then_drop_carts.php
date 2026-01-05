<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class() extends Migration
{
    public function up(): void
    {
        // Drop foreign key from cart_items if it exists, then drop carts
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'cart_id')) {
            try {
                $dbName = DB::getDatabaseName();
                $rows = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?',
                    [$dbName, 'cart_items', 'cart_id', 'carts']
                );
                if (! empty($rows)) {
                    Schema::table('cart_items', function (Blueprint $table) {
                        $table->dropForeign(['cart_id']);
                    });
                }
            } catch (\Throwable $_e) {
                // ignore any errors when checking or dropping the FK
            }
        }

        if (Schema::hasTable('carts')) {
            Schema::dropIfExists('carts');
        }
    }

    public function down(): void
    {
        // recreate carts (same as previous migration) and restore FK if possible
        if (! Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id')->nullable()->index();
                $table->string('status')->default('not_yet_ordered')->index();
                $table->decimal('total_amount', 12, 2)->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                try {
                    $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
                } catch (\Throwable $_e) {
                    // ignore if cannot create FK
                }
            });
        }
    }
};
