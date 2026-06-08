<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Warehouse;
use App\Models\Project;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function store(Request $request)
    {
        if (trim(strtolower($request->name)) === 'no allocation') {
            return back()->with('error', '"No Allocation" is a reserved name.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
        ]);
        Allocation::create($validated);
        return redirect()->route('warehouses.show', $request->warehouse_id)->with('success', 'Allocation created successfully.');
    }

    public function edit(Allocation $allocation)
    {
        if ($allocation->name === 'No Allocation') {
            return back()->with('error', 'The "No Allocation" record is reserved and cannot be edited.');
        }

        $warehouses = Warehouse::all();
        return view('supervisor.allocations.edit', compact('allocation', 'warehouses'));
    }

    public function update(Request $request, Allocation $allocation)
    {
        if ($allocation->name === 'No Allocation') {
            return back()->with('error', 'The "No Allocation" record is reserved and cannot be edited.');
        }

        if (trim(strtolower($request->name)) === 'no allocation') {
            return back()->with('error', '"No Allocation" is a reserved name.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
        ]);
        $allocation->update($validated);
        return redirect()->route('warehouses.show', $allocation->warehouse_id)->with('success', 'Allocation updated successfully.');
    }

    public function destroy(Allocation $allocation)
    {
        if ($allocation->name === 'No Allocation') {
            return back()->with('error', 'The "No Allocation" record is reserved and cannot be deleted.');
        }

        $warehouseId = $allocation->warehouse_id;
        $allocation->delete();
        return redirect()->route('warehouses.show', $warehouseId)->with('success', 'Allocation deleted successfully.');
    }
}
