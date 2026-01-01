<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ink;
use App\Models\User;
use App\Notifications\InkRestockedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class InkController extends Controller
{
    public function create()
    {
        return view('admin.inks.create');
    }

    public function store(Request $request)
    {
        // Normalize occasion inputs to canonical 'all' token if front-end sends variants
        if ($request->has('occasion')) {
            $occs = $request->input('occasion');
            if (is_array($occs)) {
                $occs = array_map(function($o) {
                    if (!is_string($o)) return $o;
                    $trim = trim($o);
                    if (strcasecmp($trim, 'ALL OCCASION') === 0 || strcasecmp($trim, 'ALL_OCCASION') === 0 || strcasecmp($trim, 'all') === 0) {
                        return 'all';
                    }
                    return $trim;
                }, $occs);
                $request->merge(['occasion' => $occs]);
            }
        }
        // ✅ Updated: Full validation for ink fields
        $validated = $request->validate([
            'material_name' => 'required|string|max:255|unique:inks,material_name',
            'occasion' => 'required|array|min:1',
            // allow 'all' so the front-end 'ALL OCCASION' option (normalized to 'all') validates
            'occasion.*' => 'string|in:all,wedding,birthday,baptism,corporate',
            'product_type' => 'required|string|in:invitation,giveaway',
            'ink_color' => 'required|string|max:50',
            'material_type' => 'nullable|string|max:50',
            'stock_qty_ml' => 'nullable|integer|min:0',
            'stock_qty' => 'required|integer|min:0', // number of cans (required for inks)
            'unit' => 'required|string|max:20', // e.g. can (required for inks)
            'size' => 'required|string|max:50', // store ml size (e.g. 500ml) (required for inks)
            'cost_per_ml' => 'required|numeric|min:0',
            'cost_per_invite' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'reorder_level' => 'required|integer|min:0',
        ]);

        // Prepare data payload for creating ink (one record per material_name)
        $selected = $validated['occasion'];
        $reorderLevel = $validated['reorder_level'];
        $base = [
            'material_name' => $validated['material_name'],
            'product_type' => $validated['product_type'],
            'ink_color' => $validated['ink_color'],
            'material_type' => $validated['material_type'] ?? 'ink',
            'stock_qty_ml' => $validated['stock_qty_ml'] ?? null,
            'stock_qty' => $validated['stock_qty'] ?? 0,
            'unit' => $validated['unit'] ?? null,
            'size' => $validated['size'] ?? null,
            'cost_per_ml' => $validated['cost_per_ml'],
            'cost_per_invite' => $validated['cost_per_invite'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
        // If 'all' selected, store as CSV of all occasions to match materials' storage
        if (in_array('all', $selected)) {
            $allOccasions = ['wedding','birthday','baptism','corporate'];
            $base['occasion'] = implode(',', $allOccasions);
        } else {
            // store selected occasions as CSV in one record to avoid duplicate material_name entries
            if (is_array($selected)) {
                $base['occasion'] = implode(',', $selected);
            } else {
                $base['occasion'] = $selected;
            }
        }

        $ink = Ink::create($base);

        $ink->inventory()->create([
            'stock_level' => $base['stock_qty'] ?? 0,
            'reorder_level' => $reorderLevel,
        ]);

        $quantityAdded = (int) ($base['stock_qty'] ?? 0);
        if ($quantityAdded > 0) {
            $owners = User::where('role', 'owner')->get();
            if ($owners->isNotEmpty()) {
                $ink->load('inventory');
                Notification::send($owners, new InkRestockedNotification($ink, $quantityAdded, Auth::user()));
            }
        }

        return redirect()->route('admin.materials.index')->with('success', 'Inks added successfully.');
    }

    public function edit(Ink $ink)
    {
        $ink->loadMissing('inventory');
        return view('admin.materials.edit_inks', compact('ink'));
    }

    public function update(Request $request, Ink $ink)
    {
        // Normalize occasion inputs (same logic as store)
        if ($request->has('occasion')) {
            $occs = $request->input('occasion');
            if (is_array($occs)) {
                $occs = array_map(function($o) {
                    if (!is_string($o)) return $o;
                    $trim = trim($o);
                    if (strcasecmp($trim, 'ALL OCCASION') === 0 || strcasecmp($trim, 'ALL_OCCASION') === 0 || strcasecmp($trim, 'all') === 0) {
                        return 'all';
                    }
                    return $trim;
                }, $occs);
                $request->merge(['occasion' => $occs]);
            }
        }

        $ink->loadMissing('inventory');
        $previousStock = (int) ($ink->inventory->stock_level ?? $ink->stock_qty ?? 0);

        $validated = $request->validate([
            'material_name' => 'required|string|max:255|unique:inks,material_name,' . $ink->id,
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'string|in:all,wedding,birthday,baptism,corporate',
            'product_type' => 'required|string|in:invitation,giveaway',
            'ink_color' => 'required|string|max:50',
            'material_type' => 'nullable|string|max:50',
            'stock_qty_ml' => 'nullable|integer|min:0',
            'stock_qty' => 'nullable|integer|min:0',
            'unit' => 'required|string|max:20',
            'size' => 'required|string|max:50',
            'cost_per_ml' => 'required|numeric|min:0',
            'cost_per_invite' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'reorder_level' => 'required|integer|min:0',
        ]);

        // Prepare update payload
        $stockLevel = $validated['stock_qty'] ?? $ink->stock_qty ?? 0;
        $reorderLevel = $validated['reorder_level'];
        $payload = [
            'material_name' => $validated['material_name'],
            'product_type' => $validated['product_type'],
            'ink_color' => $validated['ink_color'],
            'material_type' => $validated['material_type'] ?? $ink->material_type ?? 'ink',
            'stock_qty_ml' => $validated['stock_qty_ml'] ?? $ink->stock_qty_ml,
            'stock_qty' => $stockLevel,
            'unit' => $validated['unit'] ?? $ink->unit,
            'size' => $validated['size'] ?? $ink->size,
            'cost_per_ml' => $validated['cost_per_ml'],
            'cost_per_invite' => $validated['cost_per_invite'] ?? $ink->cost_per_invite,
            'description' => $validated['description'] ?? $ink->description,
        ];

        // Handle occasions similar to store: if 'all' present, store CSV of all occasions
        $selected = $validated['occasion'];
        if (in_array('all', $selected)) {
            $allOccasions = ['wedding','birthday','baptism','corporate'];
            $payload['occasion'] = implode(',', $allOccasions);
            $ink->update($payload);
        } else {
            // If multiple selected, just update the first (or you could implement multi-insert)
            if (is_array($selected)) {
                $payload['occasion'] = implode(',', $selected);
            } else {
                $payload['occasion'] = $selected;
            }
            $ink->update($payload);
        }

        if ($ink->inventory) {
            $ink->inventory->update([
                'stock_level' => $stockLevel,
                'reorder_level' => $reorderLevel,
            ]);
        } else {
            $ink->inventory()->create([
                'stock_level' => $stockLevel,
                'reorder_level' => $reorderLevel,
            ]);
        }

        $ink->load('inventory');
        $quantityAdded = $stockLevel - $previousStock;
        if ($quantityAdded > 0) {
            $owners = User::where('role', 'owner')->get();
            if ($owners->isNotEmpty()) {
                Notification::send($owners, new InkRestockedNotification($ink, $quantityAdded, Auth::user()));
            }
        }

        return redirect()->route('admin.materials.index')->with('success', 'Ink updated successfully.');
    }

    public function destroy(Ink $ink)
    {
        $ink->delete();
        return redirect()->route('admin.materials.index')->with('success', 'Ink deleted successfully.');
    }
}
