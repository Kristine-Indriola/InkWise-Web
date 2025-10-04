<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add a unique index on material_name to prevent duplicate ink entries
        if (!Schema::hasTable('inks')) return;

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Detect duplicate material_name entries and automatically merge them.
        // Strategy per duplicate material_name:
        //  - keep the row with the smallest id (keep_id)
        //  - sum `stock_qty_ml` into the kept row
        //  - for numeric cost/usage fields keep MAX() as a conservative choice
        //  - for product_type/ink_color/material_type take values from the latest row
        //  - concat distinct descriptions
        // After merging, delete the duplicate rows and continue to create the unique index.
        $duplicates = DB::select("SELECT material_name, COUNT(*) AS cnt, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids FROM inks GROUP BY material_name HAVING cnt > 1");

        if (!empty($duplicates)) {
            foreach ($duplicates as $d) {
                $name = $d->material_name;

                // Determine keep_id (smallest id) and latest_id (largest id)
                $idsRow = DB::selectOne("SELECT MIN(id) AS keep_id, MAX(id) AS latest_id FROM inks WHERE material_name = ?", [$name]);
                $keepId = $idsRow->keep_id;
                $latestId = $idsRow->latest_id;

                // Aggregate numeric and text fields
                $agg = DB::selectOne(
                    "SELECT COALESCE(SUM(stock_qty_ml), 0) AS total_stock,
                            MAX(cost_per_ml) AS max_cost,
                            MAX(avg_usage_per_invite_ml) AS max_avg_usage,
                            MAX(cost_per_invite) AS max_cost_invite,
                            GROUP_CONCAT(DISTINCT description SEPARATOR ' || ') AS merged_description
                     FROM inks WHERE material_name = ?",
                    [$name]
                );

                // Get latest-row attributes to preserve
                $latest = DB::selectOne("SELECT product_type, ink_color, material_type FROM inks WHERE id = ?", [$latestId]);

                // Update the keeper row with merged values
                DB::update(
                    "UPDATE inks SET stock_qty_ml = ?, cost_per_ml = ?, avg_usage_per_invite_ml = ?, cost_per_invite = ?, description = ?, product_type = ?, ink_color = ?, material_type = ?, updated_at = NOW() WHERE id = ?",
                    [
                        $agg->total_stock,
                        $agg->max_cost,
                        $agg->max_avg_usage,
                        $agg->max_cost_invite,
                        $agg->merged_description,
                        $latest->product_type,
                        $latest->ink_color,
                        $latest->material_type,
                        $keepId,
                    ]
                );

                // Delete the other duplicate rows for this material_name
                DB::delete("DELETE FROM inks WHERE material_name = ? AND id <> ?", [$name, $keepId]);
            }
        }

        Schema::table('inks', function (Blueprint $table) {
            try {
                $table->unique('material_name', 'material_name_unique');
            } catch (\Throwable $e) {
                // ignore — index likely exists or driver doesn't allow creation here
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inks')) return;

        Schema::table('inks', function (Blueprint $table) {
            try {
                $table->dropUnique('material_name_unique');
            } catch (\Throwable $e) {
                // ignore if index does not exist
            }
        });
    }
};
