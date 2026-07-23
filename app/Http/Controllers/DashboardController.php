<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $warehouses = [];

        if ($user->role === 'logger' || $user->role === 'viewer') {
            $warehouses = $user->warehouses;

            return view('home', compact('warehouses'));
        }

        return view('home');
    }

    public function warehouseDashboard($warehouseId)
    {
        $user = auth()->user();

        if ($user->role === 'logger' || $user->role === 'viewer') {
            // Loggers and Viewers can access a warehouse if they are explicitly assigned to it,
            // or if they are assigned to its parent warehouse.
            $isAssigned = $user->warehouses()->where('warehouses.id', $warehouseId)->active()->exists();

            if (!$isAssigned) {
                $warehouse = Warehouse::active()->with('children')->findOrFail($warehouseId);
                if ($warehouse->parent_id) {
                    $isAssignedToParent = $user->warehouses()->where('warehouses.id', $warehouse->parent_id)->active()->exists();
                    if (!$isAssignedToParent) {
                        abort(404);
                    }
                } else {
                    abort(404);
                }
            } else {
                $warehouse = $user->warehouses()->active()->with('children')->findOrFail($warehouseId);
            }
        } else {
            $warehouse = Warehouse::active()->with('children')->findOrFail($warehouseId);
        }

        // 1. Determine the target warehouses we want to include
        // If the current warehouse is a top-level parent (parent_id is null),
        // we include both itself and all of its active child/sub-warehouses.
        // Otherwise, if a sub-warehouse is selected, we only include that specific warehouse.
        if ($warehouse->parent_id === null) {
            $targetWarehouseIds = array_merge([$warehouse->id], $warehouse->children->pluck('id')->toArray());
        } else {
            $targetWarehouseIds = [$warehouse->id];
        }

        $targetWarehouses = Warehouse::whereIn('id', $targetWarehouseIds)->get()->keyBy('id');

        // 2. Fetch all combinations of item_id and warehouse_id that have movements in these warehouses
        $itemWarehousePairs = \DB::table('ledgers')
            ->select('item_id', 'warehouse_id')
            ->whereIn('warehouse_id', $targetWarehouseIds)
            ->groupBy('item_id', 'warehouse_id')
            ->get();

        // 3. Load all unique items involved in these combinations
        $itemIds = $itemWarehousePairs->pluck('item_id')->unique()->toArray();
        $itemsMap = Item::whereIn('id', $itemIds)->get()->keyBy('id');

        // 4. Construct a collection of cloned items, with specific warehouse contexts and stock levels
        $items = collect();

        foreach ($itemWarehousePairs as $pair) {
            $item = $itemsMap->get($pair->item_id);
            $wh = $targetWarehouses->get($pair->warehouse_id);

            if ($item && $wh) {
                $newItem = clone $item;
                $newItem->current_stock = $item->getBalance($wh->id);
                $newItem->warehouse_context = $wh; // Attached specific warehouse context
                $items->push($newItem);
            }
        }

        return view('logger.warehouses.dashboard', compact('warehouse', 'items'));
    }

    public function loggerRules()
    {
        $user = auth()->user();
        $warehouses = [];

        if ($user->role === 'logger' || $user->role === 'viewer') {
            $warehouses = $user->warehouses;
        }

        return view('logger.rules', compact('warehouses'));
    }

    public function createSubWarehouse($parentWarehouseId)
    {
        $user = auth()->user();

        // Ensure the logger is assigned to this warehouse and it's a top-level CENTRAL warehouse
        $parentWarehouse = $user->warehouses()
            ->active()
            ->where('type', 'CENTRAL')
            ->whereNull('parent_id')
            ->findOrFail($parentWarehouseId);

        return view('logger.warehouses.create_sub', compact('parentWarehouse'));
    }

    public function storeSubWarehouse(Request $request, $parentWarehouseId)
    {
        $user = auth()->user();

        // Ensure the logger is assigned to this warehouse and it's a top-level CENTRAL warehouse
        $parentWarehouse = $user->warehouses()
            ->active()
            ->where('type', 'CENTRAL')
            ->whereNull('parent_id')
            ->findOrFail($parentWarehouseId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $subWarehouse = Warehouse::create([
            'project_id' => $parentWarehouse->project_id,
            'parent_id' => $parentWarehouse->id,
            'type' => $parentWarehouse->type, // inherit type (CENTRAL)
            'name' => $validated['name'],
            'status' => 'ACTIVE',
        ]);

        // Automatically assign the current logger to this newly created sub-warehouse
        $subWarehouse->loggers()->attach($user->id);

        return redirect()->route('logger.warehouse.dashboard', $parentWarehouseId)
            ->with('success', 'Sub-warehouse created successfully.');
    }
}
