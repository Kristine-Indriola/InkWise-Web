<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, set all template_id values to null to avoid constraint violations
        DB::table('products')->update(['template_id' => null]);

        // Use raw SQL to add the new foreign key to product_uploads
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_template_id_foreign FOREIGN KEY (template_id) REFERENCES product_uploads(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key to product_uploads
            $table->dropForeign(['template_id']);

            // Restore the foreign key to templates
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
        });
    }
};