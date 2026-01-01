<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_template_custom';

    private string $oldTable = 'customer_template_drafts';

    public function up(): void
    {
        $this->renameExistingTable();

        if (!Schema::hasTable($this->table)) {
            $this->createFreshTable();
            return;
        }

        $this->addMissingColumns();
        $this->ensureJsonColumn($this->table, 'design');
        $this->ensureJsonColumn($this->table, 'placeholders');
        $this->ensureJsonColumn($this->table, 'preview_images');
        $this->addTimestampsIfMissing();
    }

    public function down(): void
    {
        if (Schema::hasTable($this->table) && ! Schema::hasTable($this->oldTable)) {
            Schema::rename($this->table, $this->oldTable);
        }
    }

    private function renameExistingTable(): void
    {
        if (Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::hasTable($this->oldTable)) {
            Schema::rename($this->oldTable, $this->table);
        }
    }

    private function createFreshTable(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('is_locked')->default(false)->index();
            $table->json('design')->nullable();
            $table->text('summary')->nullable();
            $table->json('placeholders')->nullable();
            $table->string('preview_image', 1024)->nullable();
            $table->json('preview_images')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function addMissingColumns(): void
    {
        $table = $this->table;

        if (! Schema::hasColumn($table, 'customer_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'user_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('customer_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'template_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('user_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'product_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->after('template_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'order_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->after('product_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'order_item_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('order_item_id')->nullable()->after('order_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'status')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('status', 32)->default('draft')->after('order_item_id')->index();
            });
        }

        if (! Schema::hasColumn($table, 'is_locked')) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('status')->index();
            });
        }

        if (! Schema::hasColumn($table, 'design')) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('design')->nullable()->after('is_locked');
            });
        }

        if (! Schema::hasColumn($table, 'summary')) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('summary')->nullable()->after('design');
            });
        }

        if (! Schema::hasColumn($table, 'placeholders')) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('placeholders')->nullable()->after('summary');
            });
        }

        if (! Schema::hasColumn($table, 'preview_image')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('preview_image', 1024)->nullable()->after('placeholders');
            });
        }

        if (! Schema::hasColumn($table, 'preview_images')) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('preview_images')->nullable()->after('preview_image');
            });
        }

        if (! Schema::hasColumn($table, 'last_edited_at')) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('last_edited_at')->nullable()->after('preview_images');
            });
        }

        if (! Schema::hasColumn($table, 'deleted_at')) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    private function addTimestampsIfMissing(): void
    {
        $table = $this->table;

        if (! Schema::hasColumn($table, 'created_at')) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('created_at')->nullable()->after('last_edited_at');
            });
        }

        if (! Schema::hasColumn($table, 'updated_at')) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        }
    }

    private function ensureJsonColumn(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $type = $this->columnType($table, $column);

        if ($type !== 'json') {
            DB::statement(sprintf('ALTER TABLE `%s` MODIFY `%s` JSON NULL', $table, $column));
        }
    }

    private function columnType(string $table, string $column): ?string
    {
        $result = DB::selectOne(
            'SELECT DATA_TYPE AS data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        );

        if (! $result) {
            return null;
        }

        $type = $result->data_type ?? null;

        return $type ? strtolower($type) : null;
    }
};