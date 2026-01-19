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
        // For SQLite, we don't need to modify the column since SQLite doesn't enforce ENUM constraints
        // The status values will be validated in the application layer
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','processing','in_production','confirmed','to_receive','completed','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, we don't need to modify the column since SQLite doesn't enforce ENUM constraints
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','confirmed','in_production','completed','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
