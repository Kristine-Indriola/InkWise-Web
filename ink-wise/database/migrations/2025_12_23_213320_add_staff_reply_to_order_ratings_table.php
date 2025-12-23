<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_ratings', function (Blueprint $table) {
            $table->text('staff_reply')->nullable();
            $table->timestamp('staff_reply_at')->nullable();
            $table->foreignId('staff_reply_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_ratings', function (Blueprint $table) {
            $table->dropForeign(['staff_reply_by']);
            $table->dropColumn(['staff_reply', 'staff_reply_at', 'staff_reply_by']);
        });
    }
};
