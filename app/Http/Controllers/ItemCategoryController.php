<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ItemCategory::withCount('items')->orderBy('name')->get();
        return view('admin.item_categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.item_categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_categories,name',
        ]);

        ItemCategory::create($validated);

        return redirect()->route('item-categories.index')->with('success', 'Item Category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemCategory $itemCategory)
    {
        return view('admin.item_categories.edit', compact('itemCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemCategory $itemCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_categories,name,' . $itemCategory->id,
        ]);

        $itemCategory->update($validated);

        return redirect()->route('item-categories.index')->with('success', 'Item Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemCategory $itemCategory)
    {
        if ($itemCategory->items()->exists()) {
            return back()->with('error', 'Cannot delete item category because it is currently assigned to one or more items.');
        }

        $itemCategory->delete();

        return redirect()->route('item-categories.index')->with('success', 'Item Category deleted successfully.');
    }
}
