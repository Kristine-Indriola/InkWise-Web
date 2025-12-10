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
            $table->dropColumn([
                'figma_file_key',
                'figma_node_id',
                'figma_url',
                'figma_metadata',
                'figma_synced_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('figma_file_key')->nullable()->after('processed_at');
            $table->string('figma_node_id')->nullable()->after('figma_file_key');
            $table->text('figma_url')->nullable()->after('figma_node_id');
            $table->json('figma_metadata')->nullable()->after('figma_url');
            $table->timestamp('figma_synced_at')->nullable()->after('figma_metadata');
        });
    }
};