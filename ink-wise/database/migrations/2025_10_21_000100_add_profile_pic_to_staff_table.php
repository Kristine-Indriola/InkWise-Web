<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('staff', 'profile_pic')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->string('profile_pic')->nullable()->after('contact_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff', 'profile_pic')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('profile_pic');
            });
        }
    }
};
