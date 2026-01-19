<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function dropForeignIfExists(string $table, string $foreignKey): void
    {
        // For SQLite, foreign keys are not enforced by default, so skip
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $constraintExists = $connection->select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$database, $table, $foreignKey]
        );

        if (!empty($constraintExists)) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKey}`");
        }
    }

    public function up(): void
    {
        // Drop foreign key on the old table name before renaming to avoid missing constraint errors
        foreach ([
            ['order_item_colors', 'order_item_colors_color_id_foreign'],
            ['order_item_colors', 'order_item_ink_usage_color_id_foreign'],
        ] as [$table, $foreignKey]) {
            if (Schema::hasTable($table)) {
                $this->dropForeignIfExists($table, $foreignKey);
            }
        }

        if (Schema::hasTable('product_colors')) {
            Schema::rename('product_colors', 'product_ink_usage');
        }

        if (Schema::hasTable('order_item_colors')) {
            Schema::rename('order_item_colors', 'order_item_ink_usage');
        }

        Schema::table('product_ink_usage', function (Blueprint $table) {
            if (Schema::hasColumn('product_ink_usage', 'name')) {
                // For SQLite, skip dropping columns
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropColumn('name');
                }
            }
            if (Schema::hasColumn('product_ink_usage', 'color_code')) {
                // For SQLite, skip dropping columns
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropColumn('color_code');
                }
            }
            if (!Schema::hasColumn('product_ink_usage', 'average_usage_ml')) {
                $table->decimal('average_usage_ml', 10, 2)->nullable()->after('product_id');
            }
        });

        // Best-effort drop FK by explicit names after rename
        foreach (['order_item_ink_usage_color_id_foreign', 'order_item_colors_color_id_foreign'] as $fkName) {
            if (Schema::hasTable('order_item_ink_usage')) {
                $this->dropForeignIfExists('order_item_ink_usage', $fkName);
            }
        }

        Schema::table('order_item_ink_usage', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['color_id', 'color_name', 'color_code', 'image_path'] as $column) {
                if (Schema::hasColumn('order_item_ink_usage', $column)) {
                    // For SQLite, skip dropping columns that might be referenced in foreign keys
                    if (DB::getDriverName() !== 'sqlite') {
                        $dropColumns[] = $column;
                    }
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
