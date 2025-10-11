<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'full_name')) {
                $table->string('full_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('addresses', 'phone')) {
                $table->string('phone')->nullable()->after('full_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('addresses', 'full_name')) {
                $table->dropColumn('full_name');
            }
        });
    }
};
