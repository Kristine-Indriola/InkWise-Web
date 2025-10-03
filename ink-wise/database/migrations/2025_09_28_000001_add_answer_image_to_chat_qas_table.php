<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_qas', function (Blueprint $table) {
            $table->string('answer_image_path')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('chat_qas', function (Blueprint $table) {
            $table->dropColumn('answer_image_path');
        });
    }
};
