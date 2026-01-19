<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_inks')) {
            // For SQLite, skip dropping foreign keys as it's not supported
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                Schema::table('product_inks', function (Blueprint $table) {
                    foreach (['product_inks_product_id_foreign', 'product_inks_ink_id_foreign'] as $fk) {
                        try {
                            $table->dropForeign($fk);
                        } catch (\Throwable $e) {
                            // ignore missing
                        }
                    }
                });
            }
            Schema::dropIfExists('product_inks');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_inks')) {
            Schema::create('product_inks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('ink_id')->nullable();
                $table->integer('qty')->nullable();
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('ink_id')->references('id')->on('inks')->onDelete('cascade');
            });
        }
    }
};
