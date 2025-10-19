<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_addons')) {
            return;
        }

        Schema::table('product_addons', function (Blueprint $table) {
            if (!Schema::hasColumn('product_addons', 'material_id')) {
                $table->foreignId('material_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('materials', 'material_id')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_addons')) {
            return;
        }

        Schema::table('product_addons', function (Blueprint $table) {
            if (Schema::hasColumn('product_addons', 'material_id')) {
                $table->dropForeign(['material_id']);
                $table->dropColumn('material_id');
            }
        });
    }
};
