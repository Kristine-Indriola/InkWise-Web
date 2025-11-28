<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ink;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function e;

class OwnerInventoryReportsController extends Controller
{
    public function index(Request $request)
    {
        $rangeKey = Str::lower((string) $request->query('range', 'all'));
        [$start, $end, $normalizedRange, $rangeLabel] = $this->resolveRange($rangeKey);

        $items = $this->loadInventoryItems();
        $filteredItems = $this->applyRangeFilter($items, $start, $end);
        $effectiveItems = $normalizedRange === 'all' ? $items : $filteredItems;

        $metrics = $this->calculateMetrics($effectiveItems);
        $summaryCards = $this->buildSummaryCards($metrics, $rangeLabel);
        $tableRows = $this->buildTableRows($effectiveItems);
        $chartSeries = $this->buildStockComparisonChart($effectiveItems);

        return view('owner.reports.inventory', [
            'summaryCards' => $summaryCards,
            'charts' => [[
                'id' => 'inventoryChart',
                'title' => 'Inventory Distribution',
                'series' => $chartSeries,
            ]],
            'tableTitle' => 'Inventory Snapshot',
            'tableSubtitle' => 'Items prioritized by stock status and estimated value.',
            'tableConfig' => [
                'headers' => ['Item', 'Category', 'On Hand', 'Reorder Level', 'Status', 'Value (PHP)'],
                'rows' => $tableRows,
                'emptyText' => $normalizedRange === 'all'
                    ? 'No inventory records found.'
                    : 'No inventory updates within the selected range.',
                'showEmpty' => true,
            ],
            'pageSubtitle' => 'Inventory health, replenishment alerts, and exportable summaries • ' . $rangeLabel,
            'activeRange' => $normalizedRange,
            'rangeReload' => true,
            'showGenerateControls' => false,
        ]);
    }

    protected function loadInventoryItems(): Collection
    {
        $materials = Material::with('inventory')->get()->map(fn (Material $material) => $this->transformMaterial($material));
        $inks = Ink::with('inventory')->get()->map(fn (Ink $ink) => $this->transformInk($ink));

        return $materials->concat($inks)->filter()->values();
    }

    protected function transformMaterial(Material $material): array
    {
        $inventory = $material->inventory;
        $stock = (int) ($inventory?->stock_level ?? $material->stock_qty ?? 0);
        $reorder = $inventory?->reorder_level ?? $material->reorder_point ?? null;
        $unitCost = (float) ($material->unit_cost ?? 0);
        $status = $this->determineStatus($stock, $reorder);

        return [
            'id' => 'material-' . $material->material_id,
            'source' => 'material',
            'material_id' => (int) $material->material_id,
            'item_name' => $material->material_name ?? 'Unnamed material',
            'category' => $material->material_type ?? 'Material',
            'stock_level' => max($stock, 0),
            'reorder_level' => $reorder !== null ? max((int) $reorder, 0) : null,
            'unit_cost' => $unitCost,
            'inventory_value' => max($stock, 0) * $unitCost,
            'status_slug' => $status['slug'],
            'status_label' => $status['label'],
            'status_badge' => $this->statusBadgeClass($status['slug']),
            'updated_at' => $this->normalizeTimestamp($inventory?->updated_at ?? $material->updated_at ?? null),
        ];
    }

    protected function transformInk(Ink $ink): array
    {
        $inventory = $ink->inventory;
        $stock = (int) ($inventory?->stock_level ?? $ink->stock_qty ?? $ink->stock_qty_ml ?? 0);
        $reorder = $inventory?->reorder_level ?? null;
        $unitCost = (float) ($ink->cost_per_ml ?? $ink->cost_per_invite ?? 0);
        $status = $this->determineStatus($stock, $reorder);

        return [
            'id' => 'ink-' . $ink->id,
            'source' => 'ink',
            'ink_id' => (int) $ink->id,
            'item_name' => $ink->material_name ?? $ink->ink_color ?? 'Ink',
            'category' => $ink->material_type ?? 'Ink',
            'stock_level' => max($stock, 0),
            'reorder_level' => $reorder !== null ? max((int) $reorder, 0) : null,
            'unit_cost' => $unitCost,
            'inventory_value' => max($stock, 0) * $unitCost,
            'status_slug' => $status['slug'],
            'status_label' => $status['label'],
            'status_badge' => $this->statusBadgeClass($status['slug']),
            'updated_at' => $this->normalizeTimestamp($inventory?->updated_at ?? $ink->updated_at ?? null),
        ];
    }

    protected function determineStatus(int $stock, ?int $reorderLevel): array
    {
        if ($stock <= 0) {
            return ['slug' => 'out', 'label' => 'Out of Stock'];
        }

        if (!is_null($reorderLevel) && (int) $reorderLevel > 0 && $stock <= (int) $reorderLevel) {
            return ['slug' => 'low', 'label' => 'Low Stock'];
        }

        return ['slug' => 'in', 'label' => 'In Stock'];
    }

    protected function calculateMetrics(Collection $items): array
    {
        $totalUnits = (int) $items->sum('stock_level');
        $itemsTracked = $items->count();
        $lowCount = $items->where('status_slug', 'low')->count();
        $outCount = $items->where('status_slug', 'out')->count();
        $inCount = max(0, $itemsTracked - ($lowCount + $outCount));
        $totalValue = (float) $items->sum('inventory_value');

        return [
            'total_units' => $totalUnits,
            'items_tracked' => $itemsTracked,
            'low_count' => $lowCount,
            'out_count' => $outCount,
            'in_count' => $inCount,
            'total_value' => $totalValue,
        ];
    }

    protected function buildSummaryCards(array $metrics, string $rangeLabel): array
    {
        $rangeSuffix = $rangeLabel !== '' ? ' • ' . $rangeLabel : '';

        return [
            [
                'label' => 'Total Inventory',
                'chip' => ['text' => 'Units', 'accent' => true],
                'icon' => 'inventory-total',
                'value' => number_format($metrics['total_units']),
                'meta' => 'Across ' . number_format($metrics['items_tracked']) . ' items' . $rangeSuffix,
            ],
            [
                'label' => 'Low Stock Alerts',
                'chip' => ['text' => 'Alert', 'accent' => true],
                'icon' => 'inventory-low',
                'value' => number_format($metrics['low_count']),
                'meta' => 'At or below reorder level' . $rangeSuffix,
            ],
            [
                'label' => 'Out of Stock',
                'chip' => ['text' => 'Critical', 'accent' => true],
                'icon' => 'inventory-out',
                'value' => number_format($metrics['out_count']),
                'meta' => 'Requires immediate restock' . $rangeSuffix,
            ],
            [
                'label' => 'Total Inventory Value',
                'chip' => ['text' => 'Value', 'accent' => true],
                'icon' => 'inventory-pending',
                'value' => $this->formatCurrency($metrics['total_value']),
                'meta' => 'Replacement cost estimate' . $rangeSuffix,
            ],
        ];
    }

    protected function buildStockComparisonChart(Collection $items): array
    {
        $statusPriority = ['out' => 0, 'low' => 1, 'in' => 2];

        $topItems = $items
            ->sortBy(function (array $item) use ($statusPriority) {
                $priority = $statusPriority[$item['status_slug']] ?? 3;
                return sprintf('%d-%06d', $priority, 100000 - (int) $item['stock_level']);
            })
            ->take(10)
            ->values();

        $labels = $topItems->map(fn (array $item) => Str::limit($item['item_name'], 32, '…'))->all();

        $stockData = $topItems->map(fn (array $item) => max((int) $item['stock_level'], 0))->all();
        $reorderData = $topItems->map(fn (array $item) => max((int) ($item['reorder_level'] ?? 0), 0))->all();

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Current stock',
                    'data' => $stockData,
                    'backgroundColor' => 'rgba(148, 185, 255, 0.88)',
                    'borderColor' => '#4c6ef5',
                    'borderWidth' => 1.2,
                    'borderRadius' => 8,
                    'maxBarThickness' => 56,
                    'categoryPercentage' => 0.55,
                    'barPercentage' => 0.9,
                ],
                [
                    'label' => 'Reorder level',
                    'data' => $reorderData,
                    'backgroundColor' => 'rgba(249, 196, 120, 0.88)',
                    'borderColor' => '#f5a524',
                    'borderWidth' => 1.1,
                    'borderRadius' => 8,
                    'maxBarThickness' => 56,
                    'categoryPercentage' => 0.55,
                    'barPercentage' => 0.9,
                ],
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'boxWidth' => 14,
                            'boxHeight' => 14,
                            'padding' => 16,
                        ],
                    ],
                    'tooltip' => [
                        'padding' => 10,
                        'bodyFont' => ['size' => 13],
                        'titleFont' => ['size' => 13, 'weight' => '600'],
                    ],
                ],
                'scales' => [
                    'x' => [
                        'grid' => ['color' => 'rgba(148, 163, 184, 0.12)'],
                        'ticks' => [
                            'maxRotation' => 0,
                            'autoSkip' => false,
                            'font' => ['size' => 12, 'weight' => '600'],
                            'color' => '#0f172a',
                        ],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => ['color' => 'rgba(148, 163, 184, 0.16)'],
                        'ticks' => [
                            'precision' => 0,
                            'font' => ['size' => 12, 'weight' => '600'],
                            'color' => '#0f172a',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildTableRows(Collection $items): array
    {
        $statusPriority = ['out' => 0, 'low' => 1, 'in' => 2];

        return $items
            ->sortBy(function (array $item) use ($statusPriority) {
                $priority = $statusPriority[$item['status_slug']] ?? 3;
                return sprintf('%d-%06d', $priority, 100000 - (int) $item['stock_level']);
            })
            ->take(20)
            ->map(function (array $item) {
                $statusBadge = sprintf('<span class="badge %s">%s</span>', $item['status_badge'], e($item['status_label']));

                return [
                    'columns' => [
                        ['text' => $item['item_name'], 'emphasis' => true],
                        ['text' => $item['category']],
                        ['text' => number_format($item['stock_level']), 'numeric' => true],
                        ['text' => $item['reorder_level'] !== null ? number_format($item['reorder_level']) : '—', 'numeric' => true],
                        ['html' => $statusBadge],
                        ['text' => number_format($item['inventory_value'], 2), 'numeric' => true],
                    ],
                ];
            })
            ->values()
            ->all();
    }

    protected function statusBadgeClass(string $status): string
    {
        return match (Str::lower($status)) {
            'in', 'in stock' => 'badge stock-ok',
            'low', 'low stock' => 'badge stock-low',
            'out', 'out of stock' => 'badge stock-critical',
            default => 'badge',
        };
    }

    protected function formatCurrency(float $value): string
    {
        return '₱' . number_format($value, 2);
    }

    protected function resolveRange(string $rangeKey): array
    {
        $normalized = match ($rangeKey) {
            'day', 'daily', 'today' => 'daily',
            'week', 'weekly', '7d' => 'weekly',
            'month', 'monthly', '30d' => 'monthly',
            'year', 'yearly', 'ytd' => 'yearly',
            default => 'all',
        };

        $now = Carbon::now();

        return match ($normalized) {
            'daily' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $normalized,
                'Today',
            ],
            'weekly' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                $normalized,
                'Last 7 days',
            ],
            'monthly' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                $normalized,
                'Last 30 days',
            ],
            'yearly' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfDay(),
                $normalized,
                'Year to date',
            ],
            default => [null, null, 'all', 'All records'],
        };
    }

    protected function applyRangeFilter(Collection $items, ?Carbon $start, ?Carbon $end): Collection
    {
        if (!$start || !$end) {
            return $items;
        }

        $materialIds = $this->fetchAffectedMaterialIds($start, $end);

        return $items->filter(function (array $item) use ($start, $end, $materialIds) {
            $updatedAt = $item['updated_at'];

            if ($updatedAt instanceof Carbon && $updatedAt->between($start, $end, true)) {
                return true;
            }

            if (($item['source'] ?? null) === 'material' && $materialIds->contains($item['material_id'] ?? null)) {
                return true;
            }

            return false;
        })->values();
    }

    protected function fetchAffectedMaterialIds(Carbon $start, Carbon $end): Collection
    {
        return StockMovement::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('material_id')
            ->pluck('material_id')
            ->unique()
            ->values();
    }

    protected function normalizeTimestamp($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
}
