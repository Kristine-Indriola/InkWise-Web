<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_order_items';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $afterColumn = Schema::hasColumn($this->table, 'design_metadata') ? 'design_metadata' : null;

            if (!Schema::hasColumn($this->table, 'design_svg')) {
                $column = $table->longText('design_svg')->nullable();
                if ($afterColumn) {
                    $column->after($afterColumn);
                }
            }
            if (!Schema::hasColumn($this->table, 'design_json')) {
                $column = $table->longText('design_json')->nullable();
                if (Schema::hasColumn($this->table, 'design_svg')) {
                    $column->after('design_svg');
                } elseif ($afterColumn) {
                    $column->after($afterColumn);
                }
            }
            if (!Schema::hasColumn($this->table, 'canvas_width')) {
                $column = $table->integer('canvas_width')->nullable();
                if (Schema::hasColumn($this->table, 'design_json')) {
                    $column->after('design_json');
                }
            }
            if (!Schema::hasColumn($this->table, 'canvas_height')) {
                $column = $table->integer('canvas_height')->nullable();
                if (Schema::hasColumn($this->table, 'canvas_width')) {
                    $column->after('canvas_width');
                }
            }
            if (!Schema::hasColumn($this->table, 'background_color')) {
                $column = $table->string('background_color', 20)->nullable();
                if (Schema::hasColumn($this->table, 'canvas_height')) {
                    $column->after('canvas_height');
                }
            }
            if (!Schema::hasColumn($this->table, 'preview_image')) {
                $column = $table->string('preview_image', 255)->nullable();
                if (Schema::hasColumn($this->table, 'background_color')) {
                    $column->after('background_color');
                }
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
