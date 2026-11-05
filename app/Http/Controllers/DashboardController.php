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
        $isViewOnly = false;

        if ($user->role === 'logger' || $user->role === 'viewer') {
            $warehouse = Warehouse::active()->with('children')->findOrFail($warehouseId);
            $isViewOnly = ! $user->hasWarehouseAccess($warehouseId);
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

        // Fetch thresholds for target warehouses and items in a single query
        $thresholds = \App\Models\StockLevelRegistry::whereIn('warehouse_id', $targetWarehouseIds)
            ->whereIn('item_id', $itemIds)
            ->get()
            ->keyBy(function ($registry) {
                return $registry->warehouse_id . '-' . $registry->item_id;
            });

        // 4. Construct a collection of cloned items, with specific warehouse contexts and stock levels
        $items = collect();
        $lowStockAlerts = collect();

        foreach ($itemWarehousePairs as $pair) {
            $item = $itemsMap->get($pair->item_id);
            $wh = $targetWarehouses->get($pair->warehouse_id);

            if ($item && $wh) {
                $newItem = clone $item;
                $currentStock = $item->getBalance($wh->id);
                $newItem->current_stock = $currentStock;
                $newItem->warehouse_context = $wh; // Attached specific warehouse context
                $items->push($newItem);

                // Check threshold
                $registryKey = $wh->id . '-' . $item->id;
                $thresholdRegistry = $thresholds->get($registryKey);
                if ($thresholdRegistry && $currentStock < $thresholdRegistry->threshold) {
                    $lowStockAlerts->push([
                        'item_name' => $item->name,
                        'warehouse_name' => $wh->name,
                        'current_stock' => $currentStock,
                        'threshold' => $thresholdRegistry->threshold,
                        'unit' => $item->unit,
                    ]);
                }
            }
        }

        return view('logger.warehouses.dashboard', compact('warehouse', 'items', 'lowStockAlerts', 'isViewOnly'));
    }

    public function exploreWarehouses(Request $request)
    {
        $user = auth()->user();

        // Retrieve all assigned IDs including sub-warehouses
        $assignedIds = $user->warehouses()->pluck('warehouses.id')->toArray();
        $assignedParentIds = $user->warehouses()->whereNull('parent_id')->pluck('warehouses.id')->toArray();
        $childAssignedIds = Warehouse::whereIn('parent_id', $assignedParentIds)->pluck('id')->toArray();
        $allAssignedIds = array_unique(array_merge($assignedIds, $childAssignedIds));

        $query = Warehouse::active()
            ->whereNull('parent_id')
            ->whereNotIn('id', $allAssignedIds)
            ->with(['project', 'children']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%")
                    ->orWhereHas('project', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $otherWarehouses = $query->paginate(12)->withQueryString();

        return view('logger.warehouses.explore', compact('otherWarehouses'));
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

    public function printInventory($warehouseId)
    {
        $user = auth()->user();

        $warehouse = Warehouse::active()->with('children')->findOrFail($warehouseId);

        if ($warehouse->parent_id === null) {
            $targetWarehouseIds = array_merge([$warehouse->id], $warehouse->children->pluck('id')->toArray());
        } else {
            $targetWarehouseIds = [$warehouse->id];
        }

        $targetWarehouses = Warehouse::whereIn('id', $targetWarehouseIds)->get()->keyBy('id');

        $itemWarehousePairs = \DB::table('ledgers')
            ->select('item_id', 'warehouse_id')
            ->whereIn('warehouse_id', $targetWarehouseIds)
            ->groupBy('item_id', 'warehouse_id')
            ->get();

        $itemIds = $itemWarehousePairs->pluck('item_id')->unique()->toArray();
        $itemsMap = Item::whereIn('id', $itemIds)->get()->keyBy('id');

        $items = collect();

        foreach ($itemWarehousePairs as $pair) {
            $item = $itemsMap->get($pair->item_id);
            $wh = $targetWarehouses->get($pair->warehouse_id);

            if ($item && $wh) {
                $currentStock = $item->getBalance($wh->id);
                // Do not include empty ledgers or zero inventory ledgers
                if ($currentStock > 0) {
                    $newItem = clone $item;
                    $newItem->current_stock = $currentStock;
                    $newItem->warehouse_context = $wh;
                    $items->push($newItem);
                }
            }
        }

        $items = $items->sortBy('name')->values();

        return view('logger.warehouses.print_inventory', compact('warehouse', 'items'));
    }
}
