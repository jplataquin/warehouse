<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class PublicDashboardController extends Controller
{
    public function show($token)
    {
        $warehouse = Warehouse::active()->where('public_token', $token)->firstOrFail();

        // Efficiently fetch only items that have movements in this warehouse
        // and calculate their balance
        $items = Item::whereHas('ledgers', function ($query) use ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        })
            ->get()
            ->map(function ($item) use ($warehouse) {
                $item->current_stock = $item->getBalance($warehouse->id);

                return $item;
            })
            ->filter(function ($item) {
                return $item->current_stock > 0;
            });

        return view('public.dashboard', compact('warehouse', 'items'));
    }

    public function getStock(Request $request, $itemId)
    {
        $token = $request->query('token');
        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $warehouse = Warehouse::active()->where('public_token', $token)->firstOrFail();
        $item = Item::findOrFail($itemId);
        $balance = $item->getBalance($warehouse->id);

        return response()->json([
            'balance' => $balance,
            'unit' => $item->unit,
        ]);
    }
}
