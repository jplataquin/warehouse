<?php

namespace App\Http\Controllers;

use App\Models\ItemType;
use Illuminate\Http\Request;

class ItemTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $itemTypes = ItemType::withCount('items')->get();
        return view('admin.item_types.index', compact('itemTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.item_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_types,name',
            'base_behavior' => 'required|in:CONSUMABLE,ASSET',
        ]);

        ItemType::create($validated);

        return redirect()->route('item-types.index')->with('success', 'Custom Item Type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemType $itemType)
    {
        return view('admin.item_types.edit', compact('itemType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemType $itemType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_types,name,' . $itemType->id,
            'base_behavior' => 'required|in:CONSUMABLE,ASSET',
        ]);

        $itemType->update($validated);

        return redirect()->route('item-types.index')->with('success', 'Custom Item Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemType $itemType)
    {
        if ($itemType->items()->exists()) {
            return back()->with('error', 'Cannot delete item type because it is currently assigned to one or more items.');
        }

        // Prevent deleting default/core types if they are still needed
        if (in_array(strtolower($itemType->name), ['consumable', 'asset'])) {
            return back()->with('error', 'Cannot delete default system item types.');
        }

        $itemType->delete();

        return redirect()->route('item-types.index')->with('success', 'Custom Item Type deleted successfully.');
    }
}
