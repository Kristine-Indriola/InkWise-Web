<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $cartsTable = 'carts';
    private string $oldItemsTable = 'cart_items';
    private string $newItemsTable = 'customer_cart_items';
    private string $summaryTable = 'customer_order_summary';

    public function up(): void
    {
        $this->migrateCartsToSummaries();
        $this->renameAndTransformItems();
        $this->dropLegacyCarts();
    }

    public function down(): void
    {
        // Restore carts table in its prior basic shape
        if (!Schema::hasTable($this->cartsTable)) {
            Schema::create($this->cartsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_id')->nullable()->index();
                $table->string('status')->default('not_yet_ordered')->index();
                $table->decimal('total_amount', 12, 2)->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
            });
        }

        // Rename customer_cart_items back to cart_items and drop added columns to a minimal shape
        if (Schema::hasTable($this->newItemsTable) && !Schema::hasTable($this->oldItemsTable)) {
            Schema::rename($this->newItemsTable, $this->oldItemsTable);
        }

        if (Schema::hasTable($this->oldItemsTable)) {
            Schema::table($this->oldItemsTable, function (Blueprint $table) {
                // Drop FKs if present
                foreach (['summary_id', 'order_item_id'] as $fkColumn) {
                    if (Schema::hasColumn($this->oldItemsTable, $fkColumn)) {
                        try {
                            $table->dropForeign([$fkColumn]);
                        } catch (\Throwable $_) {
                            // ignore if cannot drop
                        }
                    }
                }

                // keep a minimal legacy structure
                if (!Schema::hasColumn($this->oldItemsTable, 'cart_id')) {
                    $table->unsignedBigInteger('cart_id')->nullable()->index();
                }

                if (Schema::hasColumn($this->oldItemsTable, 'summary_id')) {
                    $table->dropColumn('summary_id');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'session_id')) {
                    $table->dropColumn('session_id');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'customer_id')) {
                    $table->dropColumn('customer_id');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'product_type')) {
                    $table->dropColumn('product_type');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'template_id')) {
                    $table->dropColumn('template_id');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'design')) {
                    $table->dropColumn('design');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'preview_images')) {
                    $table->dropColumn('preview_images');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'size')) {
                    $table->dropColumn('size');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'paper_stock')) {
                    $table->dropColumn('paper_stock');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'pre_order_status')) {
                    $table->dropColumn('pre_order_status');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'pre_order_date')) {
                    $table->dropColumn('pre_order_date');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn($this->oldItemsTable, 'metadata')) {
                    $table->dropColumn('metadata');
                }

                if (!Schema::hasColumn($this->oldItemsTable, 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable();
                }
                if (!Schema::hasColumn($this->oldItemsTable, 'quantity')) {
                    $table->unsignedInteger('quantity')->default(0);
                }
                if (!Schema::hasColumn($this->oldItemsTable, 'unit_price')) {
                    $table->decimal('unit_price', 12, 2)->default(0);
                }
                if (!Schema::hasColumn($this->oldItemsTable, 'total_price')) {
                    $table->decimal('total_price', 12, 2)->default(0);
                }
            });
        }
    }

    private function migrateCartsToSummaries(): void
    {
        if (!Schema::hasTable($this->cartsTable)) {
            return; // nothing to migrate
        }

        // Ensure summary table exists (created by earlier migration)
        if (!Schema::hasTable($this->summaryTable)) {
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

        $summaryMap = [];
        $cartItems = [];
        if (Schema::hasTable($this->oldItemsTable)) {
            $cartItems = DB::table($this->oldItemsTable)->get()->groupBy('cart_id');
        }

        $carts = DB::table($this->cartsTable)->get();
        foreach ($carts as $cart) {
            $items = $cartItems[$cart->id] ?? collect();
            $totalItems = $items->count();
            $totalQty = (int) $items->sum(function ($row) {
                return (int) ($row->quantity ?? 0);
            });

            $payload = [];
            if (!empty($cart->metadata)) {
                $decoded = json_decode($cart->metadata, true);
                if (is_array($decoded)) {
                    $payload['metadata'] = $decoded;
                }
            }
            if ($items->isNotEmpty()) {
                $payload['items'] = $items->map(function ($row) {
                    return (array) $row;
                })->values()->all();
            }
            if (!empty($cart->user_id)) {
                $payload['user_id'] = $cart->user_id;
            }

            $status = $cart->status === 'not_yet_ordered' ? 'draft' : 'active';

            $summaryId = DB::table($this->summaryTable)->insertGetId([
                'order_id' => null,
                'session_id' => $cart->session_id,
                'customer_id' => null,
                'total_items' => $totalItems,
                'total_quantity' => $totalQty,
                'subtotal_price' => $cart->total_amount ?? 0,
                'total_price' => $cart->total_amount ?? 0,
                'status' => $status,
                'summary_payload' => !empty($payload) ? json_encode($payload) : null,
                'created_at' => $cart->created_at ?? now(),
                'updated_at' => $cart->updated_at ?? now(),
            ]);

            $summaryMap[$cart->id] = $summaryId;
        }

        // Attach summary_id to cart_items before we rename the table
        if (Schema::hasTable($this->oldItemsTable) && !empty($summaryMap)) {
            foreach ($summaryMap as $cartId => $summaryId) {
                DB::table($this->oldItemsTable)
                    ->where('cart_id', $cartId)
                    ->update(['summary_id' => $summaryId]);
            }
        }
    }

    private function renameAndTransformItems(): void
    {
        if (!Schema::hasTable($this->oldItemsTable) && !Schema::hasTable($this->newItemsTable)) {
            return;
        }

        // Rename cart_items -> customer_cart_items if needed
        if (Schema::hasTable($this->oldItemsTable) && !Schema::hasTable($this->newItemsTable)) {
            Schema::rename($this->oldItemsTable, $this->newItemsTable);
        }

        if (!Schema::hasTable($this->newItemsTable)) {
            return;
        }

        // Clean invalid customer_id references before adding foreign keys
        $this->cleanInvalidCustomerIds();

        // Drop existing FK constraints gracefully before re-adding
        $this->dropForeignIfExistsByColumn($this->newItemsTable, 'summary_id');
        $this->dropForeignIfExistsByColumn($this->newItemsTable, 'customer_id');

        Schema::table($this->newItemsTable, function (Blueprint $table) {
            // summary_id FK
            if (!Schema::hasColumn($this->newItemsTable, 'summary_id')) {
                $table->unsignedBigInteger('summary_id')->nullable()->after('id')->index();
            }
            // session/customer linkage
            if (!Schema::hasColumn($this->newItemsTable, 'session_id')) {
                $table->string('session_id', 191)->nullable()->after('summary_id')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('session_id')->index();
            }

            // product descriptors
            if (!Schema::hasColumn($this->newItemsTable, 'product_type')) {
                $table->enum('product_type', ['invitation', 'envelope', 'giveaway'])->default('invitation')->after('customer_id')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('product_type')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'design')) {
                $table->json('design')->nullable()->after('template_id');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'preview_images')) {
                $table->json('preview_images')->nullable()->after('design');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'quantity')) {
                $table->unsignedInteger('quantity')->default(0)->after('preview_images');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'size')) {
                $table->string('size', 100)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'paper_stock')) {
                $table->json('paper_stock')->nullable()->after('size');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'pre_order_status')) {
                $table->enum('pre_order_status', ['none', 'pre_order', 'available'])->default('none')->after('paper_stock')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'pre_order_date')) {
                $table->date('pre_order_date')->nullable()->after('pre_order_status');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('pre_order_date');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'total_price')) {
                $table->decimal('total_price', 12, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'status')) {
                $table->enum('status', ['active', 'draft', 'removed'])->default('draft')->after('total_price')->index();
            }
            if (!Schema::hasColumn($this->newItemsTable, 'metadata')) {
                $table->json('metadata')->nullable()->after('status');
            }
            if (!Schema::hasColumn($this->newItemsTable, 'deleted_at')) {
                $table->softDeletes();
            }

            // Add FK constraints
            try {
                $table->foreign('summary_id')->references('id')->on($this->summaryTable)->onDelete('cascade');
            } catch (\Throwable $_) {
                // ignore if already exists
            }
            try {
                $table->foreign('customer_id')->references('customer_id')->on('customer_orders')->onDelete('set null');
            } catch (\Throwable $_) {
                // ignore if fk cannot be created
            }
        });

        // Drop legacy cart_id column if present (after data migration)
        if (Schema::hasColumn($this->newItemsTable, 'cart_id')) {
            // For SQLite, we can't drop columns easily, so we'll leave it for now
            if (DB::getDriverName() !== 'sqlite') {
                $this->dropForeignIfExistsByColumn($this->newItemsTable, 'cart_id');
                Schema::table($this->newItemsTable, function (Blueprint $table) {
                    $table->dropColumn('cart_id');
                });
            }
        }

        // Populate session_id and status defaults for migrated rows
        if (Schema::hasColumn($this->newItemsTable, 'summary_id')) {
            $items = DB::table($this->newItemsTable)->get();
            foreach ($items as $item) {
                $session = $item->session_id;
                $status = $item->status ?? 'draft';

                if (!$session && isset($item->summary_id)) {
                    $session = DB::table($this->summaryTable)->where('id', $item->summary_id)->value('session_id');
                }

                DB::table($this->newItemsTable)
                    ->where('id', $item->id)
                    ->update([
                        'session_id' => $session,
                        'status' => $status ?: 'draft',
                        'pre_order_status' => $item->pre_order_status ?: 'none',
                    ]);
            }
        }
    }

    private function dropLegacyCarts(): void
    {
        if (Schema::hasTable($this->cartsTable)) {
            Schema::dropIfExists($this->cartsTable);
        }
    }

    private function dropForeignIfExistsByColumn(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite doesn't support dropping foreign keys by name easily
                // We'll skip this for SQLite as foreign keys are not enforced anyway
                return;
            }

            $dbName = DB::getDatabaseName();
            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$dbName, $table, $column]
            );

            foreach ($constraints as $constraint) {
                $name = $constraint->CONSTRAINT_NAME ?? null;
                if (!$name) {
                    continue;
                }

                try {
                    DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $name . '`');
                } catch (\Throwable $_) {
                    // ignore if cannot drop
                }
            }
        } catch (\Throwable $_) {
            // ignore lookup failures
        }
    }

    private function cleanInvalidCustomerIds(): void
    {
        if (!Schema::hasTable($this->newItemsTable)) {
            return;
        }

        try {
            $validCustomerIds = DB::table('customer_orders')->pluck('customer_id')->filter()->unique()->values();

            DB::table($this->newItemsTable)
                ->whereNotNull('customer_id')
                ->when($validCustomerIds->isNotEmpty(), function ($query) use ($validCustomerIds) {
                    $query->whereNotIn('customer_id', $validCustomerIds);
                }, function ($query) {
                    $query->whereRaw('1 = 1');
                })
                ->update(['customer_id' => null]);
        } catch (\Throwable $_) {
            // If anything goes wrong, leave the data untouched and let the FK creation be skipped gracefully
        }
    }
};
