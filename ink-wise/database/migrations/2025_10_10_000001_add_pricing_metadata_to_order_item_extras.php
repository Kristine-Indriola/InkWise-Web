<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('order_item_addons')) {
			return;
		}

		Schema::table('order_item_addons', function (Blueprint $table) {
			if (!Schema::hasColumn('order_item_addons', 'pricing_metadata')) {
				$table->json('pricing_metadata')->nullable()->after('addon_price');
			}

			if (!Schema::hasColumn('order_item_addons', 'quantity')) {
				$table->unsignedInteger('quantity')->default(1)->after('addon_price');
			}

			if (!Schema::hasColumn('order_item_addons', 'pricing_mode')) {
				$table->string('pricing_mode', 32)->nullable()->after('pricing_metadata');
			}
		});
	}

	public function down(): void
	{
		if (!Schema::hasTable('order_item_addons')) {
			return;
		}

		Schema::table('order_item_addons', function (Blueprint $table) {
			if (Schema::hasColumn('order_item_addons', 'pricing_mode')) {
				$table->dropColumn('pricing_mode');
			}

			if (Schema::hasColumn('order_item_addons', 'pricing_metadata')) {
				$table->dropColumn('pricing_metadata');
			}

			if (Schema::hasColumn('order_item_addons', 'quantity')) {
				$table->dropColumn('quantity');
			}
		});
	}
};

