<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->latest()->paginate(50)->withQueryString();

        return view('supervisor.items.index', compact('items'));
    }

    public function assets(Request $request)
    {
        $query = Item::where('type', 'ASSET')->with('currentWarehouse');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%");
            });
        }

        $assets = $query->get();

        return view('supervisor.items.assets', compact('assets'));
    }

    public function create()
    {
        return view('supervisor.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:CONSUMABLE,ASSET',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:Operational,Out of Order',
        ]);

        $exists = Item::withTrashed()
            ->where('name', $validated['name'])
            ->where('specification', $validated['specification'] ?? null)
            ->where('unit', $validated['unit'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'An item with this exact name, specification, and unit already exists.'
            ]);
        }

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function edit(Item $item)
    {
        return view('supervisor.items.edit', compact('item'));
    }

    public function show(Item $item)
    {
        return redirect()->route('items.edit', $item);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'type' => 'required|in:CONSUMABLE,ASSET',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:Operational,Out of Order',
        ]);

        $existingItem = Item::withTrashed()
            ->where('name', $validated['name'])
            ->where('specification', $validated['specification'] ?? null)
            ->where('unit', $validated['unit'])
            ->where('id', '!=', $item->id)
            ->first();

        if ($existingItem) {
            return back()->withInput()->withErrors([
                'name' => "An item with this exact name, specification, and unit already exists. (ID: {$existingItem->id}, Name: {$existingItem->name}, Specification: " . ($existingItem->specification ?? 'N/A') . ", Unit: {$existingItem->unit})"
            ]);
        }

        $item->update($validated);

        return redirect()->route('items.index', $request->query())->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to delete items.');
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }

    public function getStock(Request $request, Item $item)
    {
        $warehouseId = $request->query('warehouse_id');
        $balance = $item->getBalance($warehouseId);

        return response()->json([
            'balance' => $balance,
            'unit' => $item->unit,
        ]);
    }

    public function updateStatus(Request $request, Item $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:Operational,Out of Order',
        ]);

        $item->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Asset status updated successfully.');
    }

    public function searchMergeTargets(Request $request, Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $query = $request->query('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $driver = \DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $concatExpr = "name || ' ' || COALESCE(specification, '') || ' ' || unit";
        } else {
            $concatExpr = "CONCAT(name, ' ', COALESCE(specification, ''), ' ', unit)";
        }

        $items = Item::withTrashed()
            ->where('id', '!=', $item->id)
            ->whereRaw("{$concatExpr} LIKE ?", ["%{$query}%"])
            ->limit(20)
            ->get(['id', 'name', 'specification', 'unit', 'type']);

        return response()->json($items);
    }

    public function mergeForm(Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $ledgerCount = \App\Models\Ledger::where('item_id', $item->id)->count();

        return view('supervisor.items.merge', compact('item', 'ledgerCount'));
    }

    public function merge(Request $request, Item $item)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to merge items.');
        }

        $validated = $request->validate([
            'target_item_id' => 'required|exists:items,id|not_in:' . $item->id,
            'confirm_merge' => 'required|accepted',
        ], [
            'target_item_id.required' => 'You must select a target item to merge into.',
            'target_item_id.exists' => 'The selected target item does not exist.',
            'target_item_id.not_in' => 'Cannot merge an item with itself.',
            'confirm_merge.accepted' => 'You must check the confirmation box to proceed.',
            'confirm_merge.required' => 'You must check the confirmation box to proceed.',
        ]);

        $targetItemId = $validated['target_item_id'];

        \DB::transaction(function () use ($item, $targetItemId) {
            // Reassign ledger records
            \App\Models\Ledger::where('item_id', $item->id)->update(['item_id' => $targetItemId]);

            // Reassign asset utilization records
            \App\Models\AssetUtilization::where('item_id', $item->id)->update(['item_id' => $targetItemId]);

            // Soft-delete the source item
            $item->delete();
        });

        return redirect()->route('items.index')->with('success', 'Items consolidated successfully.');
    }
}
