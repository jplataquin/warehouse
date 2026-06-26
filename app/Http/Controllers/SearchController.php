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

        $keywords = array_filter(explode(' ', $query));

        $warehouses = collect();
        if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
            $warehouses = Warehouse::where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->where('name', 'LIKE', "%{$keyword}%");
                }
            })
            ->limit(10)
            ->get();
        }

        $ledgers = Ledger::with(['item', 'warehouse', 'project'])
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->where(function($subQ) use ($keyword) {
                        $subQ->where('po_number', 'LIKE', "%{$keyword}%")
                             ->orWhere('offical_receipt', 'LIKE', "%{$keyword}%")
                             ->orWhere('delivery_receipt', 'LIKE', "%{$keyword}%")
                             ->orWhere('plate_no', 'LIKE', "%{$keyword}%")
                             ->orWhere('remarks', 'LIKE', "%{$keyword}%")
                             ->orWhereHas('item', function($itemQ) use ($keyword) {
                                 $itemQ->where('name', 'LIKE', "%{$keyword}%")
                                       ->orWhere('specification', 'LIKE', "%{$keyword}%")
                                       ->orWhere('type', 'LIKE', "%{$keyword}%");
                             });
                    });
                }
            })
            ->latest('entry_date')
            ->paginate(20)
            ->appends(['query' => $query]);

        return view('search.results', compact('query', 'warehouses', 'ledgers'));
    }
}
