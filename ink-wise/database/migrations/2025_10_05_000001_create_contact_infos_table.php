<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('contact_infos', function (Blueprint $table) {
			$table->id();
			$table->string('label');
			$table->string('icon')->nullable();
			$table->string('value')->nullable();
			$table->unsignedInteger('display_order')->default(0);
			$table->boolean('is_active')->default(true);
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('contact_infos');
	}
};
