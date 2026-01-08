<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // product_addons -> product_sizes (table + columns)
        if (Schema::hasTable('product_addons')) {
            Schema::rename('product_addons', 'product_sizes');
        }

        if (Schema::hasTable('product_sizes')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('product_sizes', 'addon_type')) {
                    $table->renameColumn('addon_type', 'size_type');
                }
                if (Schema::hasColumn('product_sizes', 'name')) {
                    $table->renameColumn('name', 'size');
                }
            });
        }

        // order_item_addons -> order_item_sizes (table + columns + FK)
        if (Schema::hasTable('order_item_addons')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                if (Schema::hasColumn('order_item_addons', 'addon_id')) {
                    $table->dropForeign(['addon_id']);
                }
            });
            Schema::rename('order_item_addons', 'order_item_sizes');
        }

        if (Schema::hasTable('order_item_sizes')) {
            Schema::table('order_item_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('order_item_sizes', 'addon_id')) {
                    $table->renameColumn('addon_id', 'size_id');
                }
                if (Schema::hasColumn('order_item_sizes', 'addon_type')) {
                    $table->renameColumn('addon_type', 'size_type');
                }
                if (Schema::hasColumn('order_item_sizes', 'addon_name')) {
                    $table->renameColumn('addon_name', 'size');
                }
                if (Schema::hasColumn('order_item_sizes', 'addon_price')) {
                    $table->renameColumn('addon_price', 'size_price');
                }
            });

            Schema::table('order_item_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('order_item_sizes', 'size_id')) {
                    $table->foreign('size_id')->references('id')->on('product_sizes')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Reverse order_item_sizes -> order_item_addons
        if (Schema::hasTable('order_item_sizes')) {
            Schema::table('order_item_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('order_item_sizes', 'size_id')) {
                    $table->dropForeign(['size_id']);
                }
            });

            Schema::table('order_item_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('order_item_sizes', 'size_id')) {
                    $table->renameColumn('size_id', 'addon_id');
                }
                if (Schema::hasColumn('order_item_sizes', 'size_type')) {
                    $table->renameColumn('size_type', 'addon_type');
                }
                if (Schema::hasColumn('order_item_sizes', 'size')) {
                    $table->renameColumn('size', 'addon_name');
                }
                if (Schema::hasColumn('order_item_sizes', 'size_price')) {
                    $table->renameColumn('size_price', 'addon_price');
                }
            });

            Schema::rename('order_item_sizes', 'order_item_addons');
        }

        // Reverse product_sizes -> product_addons
        if (Schema::hasTable('product_sizes')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                if (Schema::hasColumn('product_sizes', 'size_type')) {
                    $table->renameColumn('size_type', 'addon_type');
                }
                if (Schema::hasColumn('product_sizes', 'size')) {
                    $table->renameColumn('size', 'name');
                }
            });

            Schema::rename('product_sizes', 'product_addons');
        }
    }
};
