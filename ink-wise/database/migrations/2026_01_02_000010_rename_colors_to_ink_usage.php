<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key on the old table name before renaming to avoid missing constraint errors
        if (Schema::hasTable('order_item_colors')) {
            Schema::table('order_item_colors', function (Blueprint $table) {
                $fkNames = [
                    'order_item_colors_color_id_foreign',
                    'order_item_ink_usage_color_id_foreign',
                ];
                foreach ($fkNames as $fk) {
                    try {
                        $table->dropForeign($fk);
                    } catch (\Throwable $e) {
                        // ignore if not present
                    }
                }
            });
        }

        if (Schema::hasTable('product_colors')) {
            Schema::rename('product_colors', 'product_ink_usage');
        }

        if (Schema::hasTable('order_item_colors')) {
            Schema::rename('order_item_colors', 'order_item_ink_usage');
        }

        Schema::table('product_ink_usage', function (Blueprint $table) {
            if (Schema::hasColumn('product_ink_usage', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('product_ink_usage', 'color_code')) {
                $table->dropColumn('color_code');
            }
            if (!Schema::hasColumn('product_ink_usage', 'average_usage_ml')) {
                $table->decimal('average_usage_ml', 10, 2)->nullable()->after('product_id');
            }
        });

        // Best-effort drop FK by explicit names after rename
        foreach (['order_item_ink_usage_color_id_foreign', 'order_item_colors_color_id_foreign'] as $fkName) {
            try {
                DB::statement("ALTER TABLE `order_item_ink_usage` DROP FOREIGN KEY `$fkName`");
            } catch (\Throwable $e) {
                // ignore if not present
            }
        }

        Schema::table('order_item_ink_usage', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['color_id', 'color_name', 'color_code', 'image_path'] as $column) {
                if (Schema::hasColumn('order_item_ink_usage', $column)) {
                    $dropColumns[] = $column;
                }
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
            if (!Schema::hasColumn('order_item_ink_usage', 'average_usage_ml')) {
                $table->decimal('average_usage_ml', 10, 2)->nullable()->after('order_item_id');
            }
        });
    }

    public function down(): void
    {
        // Recreate dropped columns with minimal definitions
        Schema::table('order_item_ink_usage', function (Blueprint $table) {
            if (!Schema::hasColumn('order_item_ink_usage', 'color_id')) {
                $table->foreignId('color_id')->nullable()->after('order_item_id');
            }
            foreach ([
                'color_name' => 'string',
                'color_code' => 'string',
                'image_path' => 'string',
            ] as $column => $type) {
                if (!Schema::hasColumn('order_item_ink_usage', $column)) {
                    $table->{$type}($column)->nullable();
                }
            }
            // Note: foreign key not re-added in down for simplicity
        });

        Schema::table('product_ink_usage', function (Blueprint $table) {
            if (!Schema::hasColumn('product_ink_usage', 'name')) {
                $table->string('name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('product_ink_usage', 'color_code')) {
                $table->string('color_code', 32)->nullable()->after('name');
            }
        });

        if (Schema::hasTable('order_item_ink_usage')) {
            Schema::rename('order_item_ink_usage', 'order_item_colors');
        }
        if (Schema::hasTable('product_ink_usage')) {
            Schema::rename('product_ink_usage', 'product_colors');
        }
    }
};
