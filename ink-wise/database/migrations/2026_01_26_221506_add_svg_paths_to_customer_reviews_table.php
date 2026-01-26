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
        Schema::table('customer_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_reviews', 'front_svg_path')) {
                $table->string('front_svg_path', 255)->nullable()->after('background_color');
            }
            if (!Schema::hasColumn('customer_reviews', 'back_svg_path')) {
                $table->string('back_svg_path', 255)->nullable()->after('front_svg_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('customer_reviews', 'front_svg_path')) {
                $table->dropColumn('front_svg_path');
            }
            if (Schema::hasColumn('customer_reviews', 'back_svg_path')) {
                $table->dropColumn('back_svg_path');
            }
        });
    }
};
