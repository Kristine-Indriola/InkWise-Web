<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id')->unique();
                $table->string('front')->nullable();
                $table->string('back')->nullable();
                $table->string('preview')->nullable();
                $table->timestamps();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->onDelete('cascade');
            });

            return;
        }

    Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('product_images', 'image_path')) {
                $table->dropColumn('image_path');
            }
            if (!Schema::hasColumn('product_images', 'front')) {
                $table->string('front')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('product_images', 'back')) {
                $table->string('back')->nullable()->after('front');
            }
            if (!Schema::hasColumn('product_images', 'preview')) {
                $table->string('preview')->nullable()->after('back');
            }

            if (!Schema::hasColumn('product_images', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('product_images', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        $this->ensureUniqueProductConstraint();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }

    protected function ensureUniqueProductConstraint(): void
    {
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes('product_images');

        if (!array_key_exists('product_images_product_id_unique', $indexes)) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->unique('product_id');
            });
        }
    }
};
