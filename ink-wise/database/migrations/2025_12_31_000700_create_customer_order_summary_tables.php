<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $summaryTable = 'customer_order_summary';
    private string $itemsTable = 'customer_order_summary_items';

    public function up(): void
    {
        $this->createSummaryTable();
        $this->createSummaryItemsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists($this->itemsTable);
        Schema::dropIfExists($this->summaryTable);
    }

    private function createSummaryTable(): void
    {
        if (Schema::hasTable($this->summaryTable)) {
            return;
        }

        Schema::create($this->summaryTable, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('session_id', 191)->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('subtotal_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->enum('status', ['draft', 'active', 'submitted', 'abandoned'])->default('draft')->index();
            $table->json('summary_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('customer_orders')->onDelete('set null');
        });
    }

    private function createSummaryItemsTable(): void
    {
        if (Schema::hasTable($this->itemsTable)) {
            return;
        }

        Schema::create($this->itemsTable, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('summary_id');
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->enum('product_type', ['invitation', 'envelope', 'giveaway'])->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->json('design')->nullable();
            $table->json('preview_images')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('size', 100)->nullable();
            $table->json('paper_stock')->nullable();
            $table->enum('pre_order_status', ['none', 'pre_order', 'available'])->default('none')->index();
            $table->date('pre_order_date')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('summary_id')->references('id')->on($this->summaryTable)->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('customer_order_items')->onDelete('set null');
        });
    }
};
