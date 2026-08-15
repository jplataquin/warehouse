<?php

namespace App\Http\Controllers;

use App\Models\StockLevelRegistry;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockLevelRegistryController extends Controller
{
    public function store(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'threshold' => 'required|numeric|min:0.01',
        ]);

        StockLevelRegistry::updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'item_id' => $validated['item_id'],
            ],
            [
                'threshold' => $validated['threshold'],
            ]
        );

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', 'Stock threshold saved successfully.');
    }

    public function destroy(Warehouse $warehouse, StockLevelRegistry $threshold)
    {
        $threshold->delete();

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', 'Stock threshold deleted successfully.');
    }
}
