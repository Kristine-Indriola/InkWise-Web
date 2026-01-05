<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'customer_finalized';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->json('design')->nullable();
                $table->json('preview_images')->nullable();
                $table->unsignedInteger('quantity')->nullable();
                $table->string('invitation_size', 191)->nullable();
                $table->json('paper_stock')->nullable();
                $table->date('estimated_date')->nullable();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->enum('status', ['pending', 'confirmed', 'processed'])->default('pending')->index();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn($this->table, 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn($this->table, 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn($this->table, 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('template_id')->index();
            }
            if (!Schema::hasColumn($this->table, 'design')) {
                $table->json('design')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn($this->table, 'preview_images')) {
                $table->json('preview_images')->nullable()->after('design');
            }
            if (!Schema::hasColumn($this->table, 'quantity')) {
                $table->unsignedInteger('quantity')->nullable()->after('preview_images');
            }
            if (!Schema::hasColumn($this->table, 'invitation_size')) {
                $table->string('invitation_size', 191)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn($this->table, 'paper_stock')) {
                $table->json('paper_stock')->nullable()->after('invitation_size');
            }
            if (!Schema::hasColumn($this->table, 'estimated_date')) {
                $table->date('estimated_date')->nullable()->after('paper_stock');
            }
            if (!Schema::hasColumn($this->table, 'total_price')) {
                $table->decimal('total_price', 12, 2)->nullable()->after('estimated_date');
            }
            if (!Schema::hasColumn($this->table, 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'processed'])->default('pending')->after('total_price')->index();
            }
            if (!Schema::hasColumn($this->table, 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn($this->table, 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn($this->table, 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};