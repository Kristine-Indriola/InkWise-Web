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
        Schema::create('fonts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Font family name
            $table->string('display_name')->nullable(); // Human readable name
            $table->enum('source', ['google', 'uploaded', 'system']); // Font source
            $table->string('file_path')->nullable(); // Path for uploaded fonts
            $table->string('google_family')->nullable(); // Google Fonts family name
            $table->json('variants')->nullable(); // Available weights/styles (e.g., ["400", "700", "italic"])
            $table->json('subsets')->nullable(); // Character subsets (e.g., ["latin", "cyrillic"])
            $table->string('category')->nullable(); // Font category (serif, sans-serif, etc.)
            $table->boolean('is_active')->default(true); // Whether font is available for use
            $table->integer('usage_count')->default(0); // Track font usage
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'is_active']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fonts');
    }
};
