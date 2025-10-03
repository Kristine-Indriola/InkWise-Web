<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_paper_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_paper_stocks', 'material_id')) {
                $table->unsignedBigInteger('material_id')
                    ->nullable()
                    ->after('product_id');
                $table->index('material_id');
                $table->foreign('material_id')
                    ->references('material_id')
                    ->on('materials')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_paper_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_paper_stocks', 'material_id')) {
                $table->dropForeign(['material_id']);
                $table->dropIndex(['material_id']);
                $table->dropColumn('material_id');
            }
        });
    }
};
