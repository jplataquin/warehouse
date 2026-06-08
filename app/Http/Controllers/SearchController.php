<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return view('search.results', [
                'query' => $query,
                'warehouses' => collect(),
                'ledgers' => collect(),
            ]);
        }

        $warehouses = collect();
        if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
            $warehouses = Warehouse::where('name', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get();
        }

        $ledgers = Ledger::with(['item', 'warehouse', 'project'])
            ->where(function($q) use ($query) {
                $q->where('po_number', 'LIKE', "%{$query}%")
                  ->orWhere('offical_receipt', 'LIKE', "%{$query}%")
                  ->orWhere('delivery_receipt', 'LIKE', "%{$query}%")
                  ->orWhere('plate_no', 'LIKE', "%{$query}%");
            })
            ->latest('entry_date')
            ->paginate(20)
            ->appends(['query' => $query]);

        return view('search.results', compact('query', 'warehouses', 'ledgers'));
    }
}
