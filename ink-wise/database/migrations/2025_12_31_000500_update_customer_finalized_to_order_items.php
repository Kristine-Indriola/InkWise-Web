<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $oldTable = 'customer_finalized';
    private string $newTable = 'customer_order_items';

    public function up(): void
    {
        // Rename table if it exists
        if (Schema::hasTable($this->oldTable) && !Schema::hasTable($this->newTable)) {
            Schema::rename($this->oldTable, $this->newTable);
        }

        if (!Schema::hasTable($this->newTable)) {
            return;
        }

        Schema::table($this->newTable, function (Blueprint $table) {
            // Ensure order_id references customer_orders
            if (Schema::hasColumn($this->newTable, 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
                $table->foreign('order_id')->references('id')->on('customer_orders')->onDelete('set null');
            }

            // Rename invitation_size -> size if present
            if (Schema::hasColumn($this->newTable, 'invitation_size') && !Schema::hasColumn($this->newTable, 'size')) {
                $table->renameColumn('invitation_size', 'size');
            }

            // product_type enum
            if (!Schema::hasColumn($this->newTable, 'product_type')) {
                $table->enum('product_type', ['invitation', 'envelope', 'giveaway'])->default('invitation')->after('product_id')->index();
            }

            // pre_order_status and pre_order_date
            if (!Schema::hasColumn($this->newTable, 'pre_order_status')) {
                $table->enum('pre_order_status', ['none', 'pre_order', 'available'])->default('none')->after('paper_stock');
            }
            if (!Schema::hasColumn($this->newTable, 'pre_order_date')) {
                $table->date('pre_order_date')->nullable()->after('pre_order_status');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable($this->newTable)) {
            Schema::table($this->newTable, function (Blueprint $table) {
                if (Schema::hasColumn($this->newTable, 'pre_order_date')) {
                    $table->dropColumn('pre_order_date');
                }
                if (Schema::hasColumn($this->newTable, 'pre_order_status')) {
                    $table->dropColumn('pre_order_status');
                }
                if (Schema::hasColumn($this->newTable, 'product_type')) {
                    $table->dropColumn('product_type');
                }

                if (Schema::hasColumn($this->newTable, 'size') && !Schema::hasColumn($this->newTable, 'invitation_size')) {
                    $table->renameColumn('size', 'invitation_size');
                }

                // drop FK if exists
                try {
                    $table->dropForeign([$this->newTable . '_order_id_foreign']);
                } catch (\Throwable $_) {
                    // ignore
                }
            });
        }

        if (Schema::hasTable($this->newTable) && !Schema::hasTable($this->oldTable)) {
            Schema::rename($this->newTable, $this->oldTable);
        }
    }
};