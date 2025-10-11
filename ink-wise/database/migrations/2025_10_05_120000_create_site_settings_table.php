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
		Schema::create('site_settings', function (Blueprint $table) {
			$table->id();
			$table->string('contact_heading')->default('Contact Us');
			$table->string('contact_company')->nullable();
			$table->text('contact_subheading')->nullable();
			$table->string('contact_address')->nullable();
			$table->string('contact_phone')->nullable();
			$table->string('contact_email')->nullable();
			$table->text('contact_hours')->nullable();
			$table->string('about_heading')->default('About Us');
			$table->text('about_body')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('site_settings');
	}
};