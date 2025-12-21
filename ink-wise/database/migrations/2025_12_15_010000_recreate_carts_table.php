<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations: ensure `carts` exists.
     *
     * @return void
     */
    public function up(): void
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('carts')) {
            Schema::dropIfExists('carts');
        }
    }
};
