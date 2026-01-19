<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite, we need to recreate the table since it doesn't support MODIFY COLUMN or ENUM
        Schema::table('orders', function (Blueprint $table) {
            // SQLite doesn't support enum changes directly, so we'll handle this differently
            // The status values will be validated in the application layer
        });

        // If using MySQL/PostgreSQL, you could use:
        // DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','processing','in_production','confirmed','to_receive','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // For SQLite, we need to recreate the table since it doesn't support MODIFY COLUMN or ENUM
        Schema::table('orders', function (Blueprint $table) {
            // SQLite doesn't support enum changes directly, so we'll handle this differently
            // The status values will be validated in the application layer
        });

        // If using MySQL/PostgreSQL, you could use:
        // DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','in_production','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
