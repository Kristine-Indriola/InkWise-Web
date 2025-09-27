<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_inks')) {
            Schema::table('product_inks', function (Blueprint $table) {
                if (!Schema::hasColumn('product_inks', 'qty')) {
                    $table->decimal('qty', 12, 2)->nullable()->after('usage');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_inks')) {
            Schema::table('product_inks', function (Blueprint $table) {
                if (Schema::hasColumn('product_inks', 'qty')) {
                    $table->dropColumn('qty');
                }
            });
        }
    }
};
