<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('date_available');
            }

            if (!Schema::hasColumn('products', 'unpublished_reason')) {
                $table->text('unpublished_reason')->nullable()->after('published_at');
            }
        });

        DB::table('products')
            ->whereNull('published_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw('1'))
                    ->from('product_uploads')
                    ->whereColumn('product_uploads.product_id', 'products.id');
            })
            ->update([
                'published_at' => Carbon::now(),
            ]);

        Schema::table('templates', function (Blueprint $table) {
            if (!Schema::hasColumn('templates', 'status_note')) {
                $table->text('status_note')->nullable()->after('status');
            }

            if (!Schema::hasColumn('templates', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'published_at')) {
                $table->dropColumn('published_at');
            }

            if (Schema::hasColumn('products', 'unpublished_reason')) {
                $table->dropColumn('unpublished_reason');
            }
        });

        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'status_note')) {
                $table->dropColumn('status_note');
            }

            if (Schema::hasColumn('templates', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
        });
    }
};
