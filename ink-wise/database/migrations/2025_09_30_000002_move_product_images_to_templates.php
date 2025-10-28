<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add front/back columns to templates
        Schema::table('templates', function (Blueprint $table) {
            $table->string('front_image')->nullable()->after('preview');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('back_image')->nullable()->after('front_image');
        });

        // Drop product_images table
        Schema::dropIfExists('product_images');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate product_images table
        Schema::create('product_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index();
            $table->string('type')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Drop columns from templates
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('back_image');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('front_image');
        });
    }
};
