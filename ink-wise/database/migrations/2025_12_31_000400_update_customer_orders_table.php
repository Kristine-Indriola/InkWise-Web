<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_orders';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'order_number')) {
                $table->string('order_number', 64)->nullable()->unique()->after('user_id');
            }

            if (!Schema::hasColumn($this->table, 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])
                    ->default('pending')
                    ->after('order_number')
                    ->index();
            }

            if (!Schema::hasColumn($this->table, 'total_price')) {
                $table->decimal('total_price', 12, 2)->nullable()->after('status');
            }

            if (!Schema::hasColumn($this->table, 'estimated_date')) {
                $table->date('estimated_date')->nullable()->after('total_price');
            }

            if (!Schema::hasColumn($this->table, 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'order_number')) {
                $table->dropUnique([$this->table . '_order_number_unique']);
                $table->dropColumn('order_number');
            }

            if (Schema::hasColumn($this->table, 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn($this->table, 'total_price')) {
                $table->dropColumn('total_price');
            }

            if (Schema::hasColumn($this->table, 'estimated_date')) {
                $table->dropColumn('estimated_date');
            }

            if (Schema::hasColumn($this->table, 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};