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
            $query->where(function($q) use ($search) {
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
            $query->where(function($q) use ($search) {
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
            'type' => 'required|in:CONSUMABLE,ASSET,RECOVERABLE',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
        ]);
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
            'type' => 'required|in:CONSUMABLE,ASSET,RECOVERABLE',
            'name' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
        ]);
        $item->update($validated);
        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }

    public function getStock(Request $request, Item $item)
    {
        $warehouseId = $request->query('warehouse_id');
        $balance = $item->getBalance($warehouseId);
        
        return response()->json([
            'balance' => $balance,
            'unit' => $item->unit
        ]);
    }
}
