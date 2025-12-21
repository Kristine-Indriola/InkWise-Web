<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations: drop the carts table if it exists.
     *
     * @return void
     */
    public function up(): void
    {
        // Ensure any foreign keys referencing `carts` are removed first
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                try {
                    $table->dropForeign(['cart_id']);
                } catch (\Throwable $_e) {
                    // ignore if the fk doesn't exist
                }
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::dropIfExists('carts');
        }
    }

    /**
     * Reverse the migrations: recreate the carts table to its previous shape.
     *
     * @return void
     */
    public function down(): void
    {
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
    }
};
