<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        
        Schema::create('addresses', function (Blueprint $table) {
            $table->id('address_id');
            
            // Foreign key to users table
            $table->foreignId('user_id')
                  ->constrained('users', 'user_id') // ✅ adjust if your PK is user_id
                  ->onDelete('cascade');

            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country')->default('Philippines');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
