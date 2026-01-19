<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we don't need to modify the column since SQLite doesn't enforce ENUM constraints
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('draft','pending','pending_awaiting_materials','processing','in_production','confirmed','to_receive','completed','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, we don't need to modify the column since SQLite doesn't enforce ENUM constraints
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('draft','pending','processing','in_production','confirmed','to_receive','completed','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
