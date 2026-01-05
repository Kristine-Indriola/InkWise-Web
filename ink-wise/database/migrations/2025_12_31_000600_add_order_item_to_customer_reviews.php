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
            if (!Schema::hasColumn($this->table, 'order_item_id')) {
                $table->unsignedBigInteger('order_item_id')->nullable()->after('template_id');
                $table->index('order_item_id');
                $table->foreign('order_item_id')
                    ->references('id')
                    ->on('customer_order_items')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'order_item_id')) {
                try {
                    $table->dropForeign(['order_item_id']);
                } catch (\Throwable $_) {
                    // Attempt by inferred constraint name
                    try {
                        $table->dropForeign($this->table . '_order_item_id_foreign');
                    } catch (\Throwable $_) {
                        // ignore if constraint already removed
                    }
                }

                $table->dropColumn('order_item_id');
            }
        });
    }
};
