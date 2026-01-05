<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $oldTable = 'customer_uploads';
    private string $table = 'customer_reviews';

    public function up(): void
    {
        $this->renameIfNeeded();
        $this->createOrUpdateTable();
    }

    public function down(): void
    {
        if (Schema::hasTable($this->table) && !Schema::hasTable($this->oldTable)) {
            Schema::rename($this->table, $this->oldTable);
        }
    }

    private function renameIfNeeded(): void
    {
        if (Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::hasTable($this->oldTable)) {
            Schema::rename($this->oldTable, $this->table);
        }
    }

    private function createOrUpdateTable(): void
    {
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->text('review_text')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn($this->table, 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('customer_id')->index();
            }

            if (!Schema::hasColumn($this->table, 'review_text')) {
                $table->text('review_text')->nullable()->after('template_id');
            }

            if (!Schema::hasColumn($this->table, 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('review_text');
            }

            if (!Schema::hasColumn($this->table, 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
};