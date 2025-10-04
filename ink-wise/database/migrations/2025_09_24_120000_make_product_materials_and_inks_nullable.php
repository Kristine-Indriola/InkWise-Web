<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement('ALTER TABLE `product_materials` DROP FOREIGN KEY `product_materials_material_id_foreign`');
        } catch (\Exception $e) {
        }
        DB::statement('ALTER TABLE `product_materials` MODIFY `material_id` BIGINT UNSIGNED NULL');
        try {
            DB::statement('ALTER TABLE `product_materials` ADD CONSTRAINT `product_materials_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {
        }

        try {
            DB::statement('ALTER TABLE `product_inks` DROP FOREIGN KEY `product_inks_ink_id_foreign`');
        } catch (\Exception $e) {
        }
        DB::statement('ALTER TABLE `product_inks` MODIFY `ink_id` BIGINT UNSIGNED NULL');
        try {
            DB::statement('ALTER TABLE `product_inks` ADD CONSTRAINT `product_inks_ink_id_foreign` FOREIGN KEY (`ink_id`) REFERENCES `inks`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement('ALTER TABLE `product_materials` DROP FOREIGN KEY `product_materials_material_id_foreign`');
        } catch (\Exception $e) {}
        DB::statement('ALTER TABLE `product_materials` MODIFY `material_id` BIGINT UNSIGNED NOT NULL');
        try {
            DB::statement('ALTER TABLE `product_materials` ADD CONSTRAINT `product_materials_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE `product_inks` DROP FOREIGN KEY `product_inks_ink_id_foreign`');
        } catch (\Exception $e) {}
        DB::statement('ALTER TABLE `product_inks` MODIFY `ink_id` BIGINT UNSIGNED NOT NULL');
        try {
            DB::statement('ALTER TABLE `product_inks` ADD CONSTRAINT `product_inks_ink_id_foreign` FOREIGN KEY (`ink_id`) REFERENCES `inks`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {}
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
