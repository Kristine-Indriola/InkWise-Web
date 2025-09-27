<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeCostPerInviteNullableInInksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to avoid requiring doctrine/dbal for simple MODIFY.
        DB::statement("ALTER TABLE `inks` MODIFY `cost_per_invite` DECIMAL(8,2) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to NOT NULL (note: this will fail if NULL values exist).
        DB::statement("ALTER TABLE `inks` MODIFY `cost_per_invite` DECIMAL(8,2) NOT NULL");
    }
}
