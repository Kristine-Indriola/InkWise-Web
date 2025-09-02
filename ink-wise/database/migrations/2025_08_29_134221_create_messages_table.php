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
    
    // Sender
    $table->unsignedBigInteger('sender_id');
    $table->string('sender_type'); // "user" or "customer"
    
    // Receiver
    $table->unsignedBigInteger('receiver_id');
    $table->string('receiver_type'); // "user" or "customer"
    
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
