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
        Schema::table('product_uploads', function (Blueprint $table) {
            // Drop file-related columns
            $table->dropColumn(['filename', 'original_name', 'mime_type', 'size']);

            // Make product_id nullable since template uploads may not be associated with a product yet
            $table->foreignId('product_id')->nullable()->change();

            // Add template-related columns
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('template_name');
            $table->text('description')->nullable();
            $table->string('product_type'); // Invitation, Giveaway, Envelope
            $table->string('event_type')->nullable();
            $table->string('theme_style')->nullable();
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('design_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_uploads', function (Blueprint $table) {
            // Drop template-related columns
            $table->dropForeign(['template_id']);
            $table->dropColumn([
                'template_id',
                'template_name',
                'description',
                'product_type',
                'event_type',
                'theme_style',
                'front_image',
                'back_image',
                'preview_image',
                'design_data'
            ]);

            // Make product_id required again
            $table->foreignId('product_id')->nullable(false)->change();

            // Restore file-related columns
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
        });
    }
};
