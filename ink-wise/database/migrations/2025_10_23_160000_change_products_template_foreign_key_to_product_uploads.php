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

        // For SQLite compatibility, we need to recreate the table since SQLite doesn't support
        // adding foreign key constraints via ALTER TABLE
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't enforce foreign keys by default, so we can skip the constraint changes
            // The foreign key will be handled at the application level
            return;
        }

        // For MySQL/PostgreSQL, drop existing foreign key and add new one
        Schema::table('products', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            try {
                $table->dropForeign(['template_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            // Add new foreign key to product_uploads
            $table->foreign('template_id')->references('id')->on('product_uploads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't enforce foreign keys, so skip
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            // Drop the foreign key to product_uploads
            $table->dropForeign(['template_id']);

            // Restore the foreign key to templates
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
        });
    }
};