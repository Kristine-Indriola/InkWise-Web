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
        // Convert enum( 'wedding','birthday','baptism','corporate' ) to a flexible varchar
        // Use raw statement to avoid requiring doctrine/dbal for change()
        DB::statement("ALTER TABLE `materials` MODIFY `occasion` VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum (note: ensure there are no invalid values before rolling back)
        DB::statement("ALTER TABLE `materials` MODIFY `occasion` ENUM('wedding','birthday','baptism','corporate') NOT NULL");
    }
};
