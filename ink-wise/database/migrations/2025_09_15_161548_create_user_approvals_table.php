<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_approvals', function (Blueprint $table) {
            $table->id();
            
            // Link to user being approved
              $table->foreignId('user_id')
                  ->constrained('users', 'user_id') // ✅ adjust if your PK is user_id
                  ->onDelete('cascade');
            
            // Who approved (the owner/admin)
            $table->foreignId('approved_by')->nullable()->constrained('users', "user_id")->onDelete('set null');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_approvals');
    }
};

