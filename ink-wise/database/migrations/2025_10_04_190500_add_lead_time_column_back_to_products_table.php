<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('products')) {
			return;
		}

		if (!Schema::hasColumn('products', 'lead_time_days')) {
			Schema::table('products', function (Blueprint $table) {
				$table->unsignedSmallInteger('lead_time_days')->nullable()->after('unit_price');
			});
		}

		if (!Schema::hasColumn('products', 'date_available')) {
			Schema::table('products', function (Blueprint $table) {
				$table->date('date_available')->nullable()->after('lead_time_days');
			});
		}
	}

	public function down(): void
	{
		if (!Schema::hasTable('products')) {
			return;
		}

		if (Schema::hasColumn('products', 'date_available')) {
			Schema::table('products', function (Blueprint $table) {
				$table->dropColumn('date_available');
			});
		}

		if (Schema::hasColumn('products', 'lead_time_days')) {
			Schema::table('products', function (Blueprint $table) {
				$table->dropColumn('lead_time_days');
			});
		}
	}
};