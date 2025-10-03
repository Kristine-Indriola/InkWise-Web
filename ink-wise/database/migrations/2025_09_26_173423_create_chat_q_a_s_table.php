<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_qas', function (Blueprint $table) {
            $table->id();
            $table->string('question');   // the question the bot looks for
            $table->text('answer');       // the response shown to the user
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_qas');
    }
};
