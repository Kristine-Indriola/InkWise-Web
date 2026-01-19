<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_reviews';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'design_svg')) {
                $table->longText('design_svg')->nullable()->after('rating');
            }

            if (!Schema::hasColumn($this->table, 'design_json')) {
                $table->longText('design_json')->nullable()->after('design_svg');
            }

            if (!Schema::hasColumn($this->table, 'preview_image')) {
                $table->string('preview_image', 255)->nullable()->after('design_json');
            }

            if (!Schema::hasColumn($this->table, 'canvas_width')) {
                $table->integer('canvas_width')->nullable()->after('preview_image');
            }

            if (!Schema::hasColumn($this->table, 'canvas_height')) {
                $table->integer('canvas_height')->nullable()->after('canvas_width');
            }

            if (!Schema::hasColumn($this->table, 'background_color')) {
                $table->string('background_color', 20)->nullable()->after('canvas_height');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'background_color')) {
                $table->dropColumn('background_color');
            }

            if (Schema::hasColumn($this->table, 'canvas_height')) {
                $table->dropColumn('canvas_height');
            }

            if (Schema::hasColumn($this->table, 'canvas_width')) {
                $table->dropColumn('canvas_width');
            }

            if (Schema::hasColumn($this->table, 'preview_image')) {
                $table->dropColumn('preview_image');
            }

            if (Schema::hasColumn($this->table, 'design_json')) {
                $table->dropColumn('design_json');
            }

            if (Schema::hasColumn($this->table, 'design_svg')) {
                $table->dropColumn('design_svg');
            }
        });
    }
};
