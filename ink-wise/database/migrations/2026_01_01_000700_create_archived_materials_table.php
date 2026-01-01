<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('original_material_id')->index();
            $table->string('material_name')->nullable();
            $table->string('material_type')->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->integer('stock_level')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_materials');
    }
};
