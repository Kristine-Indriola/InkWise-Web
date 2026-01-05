<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cart_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('product_type')->nullable();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->integer('quantity')->default(1);
                $table->unsignedBigInteger('paper_type_id')->nullable()->index();
                $table->decimal('paper_price', 12, 2)->nullable();
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total_price', 14, 2)->default(0);
                $table->enum('status', ['not_ordered', 'paid', 'done'])->default('not_ordered');
                $table->timestamps();

                $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            });
            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('cart_id')->index();
            }
            if (! Schema::hasColumn('cart_items', 'product_type')) {
                $table->string('product_type')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('cart_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('product_type')->index();
            }
            if (! Schema::hasColumn('cart_items', 'quantity')) {
                $table->integer('quantity')->default(1)->after('product_id');
            }
            if (! Schema::hasColumn('cart_items', 'paper_type_id')) {
                $table->unsignedBigInteger('paper_type_id')->nullable()->after('quantity')->index();
            }
            if (! Schema::hasColumn('cart_items', 'paper_price')) {
                $table->decimal('paper_price', 12, 2)->nullable()->after('paper_type_id');
            }
            if (! Schema::hasColumn('cart_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('paper_price');
            }
            if (! Schema::hasColumn('cart_items', 'total_price')) {
                $table->decimal('total_price', 14, 2)->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('cart_items', 'status')) {
                $table->enum('status', ['not_ordered', 'paid', 'done'])->default('not_ordered')->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('cart_items')) {
            // Attempt to remove columns we added (be conservative)
            Schema::table('cart_items', function (Blueprint $table) {
                if (Schema::hasColumn('cart_items', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('cart_items', 'total_price')) {
                    $table->dropColumn('total_price');
                }
                if (Schema::hasColumn('cart_items', 'unit_price')) {
                    $table->dropColumn('unit_price');
                }
                if (Schema::hasColumn('cart_items', 'paper_price')) {
                    $table->dropColumn('paper_price');
                }
                if (Schema::hasColumn('cart_items', 'paper_type_id')) {
                    $table->dropColumn('paper_type_id');
                }
                if (Schema::hasColumn('cart_items', 'quantity')) {
                    $table->dropColumn('quantity');
                }
                if (Schema::hasColumn('cart_items', 'product_id')) {
                    $table->dropColumn('product_id');
                }
                if (Schema::hasColumn('cart_items', 'product_type')) {
                    $table->dropColumn('product_type');
                }
                if (Schema::hasColumn('cart_items', 'customer_id')) {
                    $table->dropColumn('customer_id');
                }
            });
        }
    }
};
