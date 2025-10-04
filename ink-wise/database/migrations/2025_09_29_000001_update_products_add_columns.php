<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'base_price')) {
                $table->decimal('base_price', 12, 2)->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'lead_time')) {
                $table->string('lead_time')->nullable()->after('base_price');
            }
            if (!Schema::hasColumn('products', 'date_available')) {
                $table->date('date_available')->nullable()->after('lead_time');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->longText('description')->nullable()->after('date_available');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('products', 'date_available')) {
                $table->dropColumn('date_available');
            }
            if (Schema::hasColumn('products', 'lead_time')) {
                $table->dropColumn('lead_time');
            }
            if (Schema::hasColumn('products', 'base_price')) {
                $table->dropColumn('base_price');
            }
        });
    }
};
