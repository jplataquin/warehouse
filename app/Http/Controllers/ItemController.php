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

    public function update(Request $request, Item $item)
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
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'An item with this exact name, specification, and unit already exists.'
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
}
