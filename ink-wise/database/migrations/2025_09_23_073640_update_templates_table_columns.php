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
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('design'); // Remove old column
            $table->string('event_type')->nullable();
            $table->string('product_type')->nullable();
            $table->string('theme_style')->nullable();
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->longText('design'); // Re-add the old column if needed
            $table->dropColumn(['event_type', 'product_type', 'theme_style', 'description']);
        });
    }
};
