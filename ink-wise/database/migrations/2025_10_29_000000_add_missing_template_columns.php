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
            if (!Schema::hasColumn('templates', 'svg_path')) {
                $table->string('svg_path')->nullable()->after('preview');
            }
            if (!Schema::hasColumn('templates', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('svg_path');
            }
            if (!Schema::hasColumn('templates', 'back_svg_path')) {
                $table->string('back_svg_path')->nullable()->after('svg_path');
            }
            if (!Schema::hasColumn('templates', 'figma_file_key')) {
                $table->string('figma_file_key')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('templates', 'figma_node_id')) {
                $table->string('figma_node_id')->nullable()->after('figma_file_key');
            }
            if (!Schema::hasColumn('templates', 'figma_url')) {
                $table->text('figma_url')->nullable()->after('figma_node_id');
            }
            if (!Schema::hasColumn('templates', 'figma_metadata')) {
                $table->json('figma_metadata')->nullable()->after('figma_url');
            }
            if (!Schema::hasColumn('templates', 'figma_synced_at')) {
                $table->timestamp('figma_synced_at')->nullable()->after('figma_metadata');
            }
            if (!Schema::hasColumn('templates', 'has_back_design')) {
                $table->boolean('has_back_design')->default(false)->after('figma_synced_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $columns = ['svg_path', 'processed_at', 'back_svg_path', 'figma_file_key', 'figma_node_id', 'figma_url', 'figma_metadata', 'figma_synced_at', 'has_back_design'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};