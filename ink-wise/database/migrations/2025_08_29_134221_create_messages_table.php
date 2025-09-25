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
        Schema::create('messages', function (Blueprint $table) {
    $table->id();

    // Sender (optional if you later add authentication)
    $table->unsignedBigInteger('sender_id')->nullable();
    $table->string('sender_type')->nullable();

    // Receiver (optional, e.g. admin ID)
    $table->unsignedBigInteger('receiver_id')->nullable();
    $table->string('receiver_type')->nullable();

    // From form
    $table->string('name');
    $table->string('email');
    $table->text('message');

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
