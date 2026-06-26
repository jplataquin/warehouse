<?php

namespace App\Http\Controllers;

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
            $warehouse = $user->warehouses()->active()->findOrFail($warehouseId);
        } else {
            $warehouse = \App\Models\Warehouse::active()->findOrFail($warehouseId);
        }

        // Efficiently fetch only items that have movements in this warehouse
        // and calculate their balance using a group by query
        $items = \App\Models\Item::whereHas('ledgers', function($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->get()
        ->map(function($item) use ($warehouseId) {
            $item->current_stock = $item->getBalance($warehouseId);
            return $item;
        })
        ->filter(function($item) {
            return $item->current_stock > 0;
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
}
