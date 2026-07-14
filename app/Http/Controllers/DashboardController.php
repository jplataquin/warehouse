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

        if ($user->role === 'logger') {
            $warehouses = $user->warehouses;

            return view('home', compact('warehouses'));
        }

        return view('home');
    }

    public function warehouseDashboard($warehouseId)
    {
        $user = auth()->user();

        if ($user->role === 'logger') {
            $warehouse = $user->warehouses()->active()->with('children')->findOrFail($warehouseId);
        } else {
            $warehouse = Warehouse::active()->with('children')->findOrFail($warehouseId);
        }

        // Efficiently fetch only items that have movements in this warehouse
        // and calculate their balance using a group by query
        $items = Item::whereHas('ledgers', function ($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
            ->get()
            ->map(function ($item) use ($warehouseId) {
                $item->current_stock = $item->getBalance($warehouseId);

                return $item;
            });

        return view('logger.warehouses.dashboard', compact('warehouse', 'items'));
    }

    public function loggerRules()
    {
        $user = auth()->user();
        $warehouses = [];

        if ($user->role === 'logger') {
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
