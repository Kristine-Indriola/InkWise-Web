<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_cart_items';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'design_svg')) {
                $table->longText('design_svg')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn($this->table, 'design_json')) {
                $table->longText('design_json')->nullable()->after('design_svg');
            }
            if (!Schema::hasColumn($this->table, 'canvas_width')) {
                $table->integer('canvas_width')->nullable()->after('design_json');
            }
            if (!Schema::hasColumn($this->table, 'canvas_height')) {
                $table->integer('canvas_height')->nullable()->after('canvas_width');
            }
            if (!Schema::hasColumn($this->table, 'background_color')) {
                $table->string('background_color', 20)->nullable()->after('canvas_height');
            }
            if (!Schema::hasColumn($this->table, 'preview_image')) {
                $table->string('preview_image', 255)->nullable()->after('background_color');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            foreach (['preview_image', 'background_color', 'canvas_height', 'canvas_width', 'design_json', 'design_svg'] as $column) {
                if (Schema::hasColumn($this->table, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
