<?php

namespace App\Http\Controllers;

use App\Models\LedgerDiscrepancy;
use Illuminate\Http\Request;

class LedgerDiscrepancyController extends Controller
{
    /**
     * Display a listing of logged ledger discrepancies.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'ALL');

        $query = LedgerDiscrepancy::with(['warehouse', 'item', 'ledger', 'resolver']);

        if ($status === 'DISCREPANCY') {
            $query->where('status', 'DISCREPANCY');
        } elseif ($status === 'RESOLVED') {
            $query->where('status', 'RESOLVED');
        }

        $discrepancies = $query->latest('id')->paginate(25)->withQueryString();

        $pendingCount = LedgerDiscrepancy::where('status', 'DISCREPANCY')->count();
        $resolvedCount = LedgerDiscrepancy::where('status', 'RESOLVED')->count();

        return view('discrepancies.index', compact('discrepancies', 'status', 'pendingCount', 'resolvedCount'));
    }

    /**
     * Mark the discrepancy as resolved.
     */
    public function resolve(LedgerDiscrepancy $discrepancy)
    {
        if ($discrepancy->status === 'RESOLVED') {
            return back()->with('info', "Discrepancy #{$discrepancy->id} is already marked as resolved.");
        }

        $discrepancy->update([
            'status' => 'RESOLVED',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return back()->with('success', "Discrepancy #{$discrepancy->id} successfully marked as resolved by " . auth()->user()->name . '.');
    }
}
