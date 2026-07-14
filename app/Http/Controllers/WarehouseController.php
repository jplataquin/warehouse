<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Warehouse::with(['project', 'loggers', 'parent']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $warehouses = $query->get();

        return view('supervisor.warehouses.index', compact('warehouses'));
    }

    public function show($warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $warehouse->load(['project', 'loggers', 'allocations', 'parent', 'children']);
        $availableLoggers = User::where('role', 'logger')
            ->whereDoesntHave('warehouses', function ($query) use ($warehouse) {
                $query->where('warehouses.id', $warehouse->id);
            })->get();

        return view('supervisor.warehouses.show', compact('warehouse', 'availableLoggers'));
    }

    public function assignLogger(Request $request, $warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $validated = $request->validate([
            'logger_id' => 'required|exists:users,id',
        ]);

        $warehouse->loggers()->attach($validated['logger_id']);

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Logger added successfully.');
    }

    public function removeLogger($warehouse, $logger)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $loggerId = $logger instanceof User ? $logger->id : $logger;

        $warehouse->loggers()->detach($loggerId);

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Logger removed successfully.');
    }

    public function create()
    {
        $projects = Project::all();
        $loggers = User::where('role', 'logger')->get();
        $parentWarehouses = Warehouse::where('type', 'CENTRAL')
            ->whereNull('parent_id')
            ->active()
            ->get();

        return view('supervisor.warehouses.create', compact('projects', 'loggers', 'parentWarehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'parent_id' => 'nullable|exists:warehouses,id',
            'type' => 'required|in:SITE,CENTRAL,EQUIPMENT/VEHICLE,OFFICE/FACILITY',
            'name' => 'required|string|max:255',
            'status' => 'required|in:Active,Deactivated,ACTIVE,DEACTIVATED',
            'logger_ids' => 'nullable|array',
            'logger_ids.*' => 'exists:users,id',
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Warehouse::findOrFail($validated['parent_id']);
            $validated['project_id'] = $parent->project_id;
            $validated['type'] = 'CENTRAL';
        }

        $validated['status'] = strtoupper($validated['status']);
        $warehouse = Warehouse::create($validated);

        if ($request->has('logger_ids')) {
            $warehouse->loggers()->sync($request->logger_ids);
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function edit($warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }
        $projects = Project::all();
        $loggers = User::where('role', 'logger')->get();
        $parentWarehouses = Warehouse::where('type', 'CENTRAL')
            ->whereNull('parent_id')
            ->where('id', '!=', $warehouse->id)
            ->active()
            ->get();

        return view('supervisor.warehouses.edit', compact('warehouse', 'projects', 'loggers', 'parentWarehouses'));
    }

    public function update(Request $request, $warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'parent_id' => 'nullable|exists:warehouses,id',
            'type' => 'required|in:SITE,CENTRAL,EQUIPMENT/VEHICLE,OFFICE/FACILITY',
            'name' => 'required|string|max:255',
            'status' => 'required|in:Active,Deactivated,ACTIVE,DEACTIVATED',
            'logger_ids' => 'nullable|array',
            'logger_ids.*' => 'exists:users,id',
        ]);

        if (!empty($validated['parent_id'])) {
            if ($validated['parent_id'] == $warehouse->id) {
                return back()->withErrors(['parent_id' => 'A warehouse cannot be its own parent.'])->withInput();
            }
            $parent = Warehouse::findOrFail($validated['parent_id']);
            $validated['project_id'] = $parent->project_id;
            $validated['type'] = 'CENTRAL';
        } else {
            // If parent is removed, keep original fields or let user set them
        }

        $validated['status'] = strtoupper($validated['status']);
        $warehouse->update($validated);

        $warehouse->loggers()->sync($request->logger_ids ?? []);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy($warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }

    public function generatePublicToken($warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $warehouse->update([
            'public_token' => Str::random(32),
        ]);

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Public dashboard link generated successfully.');
    }

    public function revokePublicToken($warehouse)
    {
        if (! $warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $warehouse->update([
            'public_token' => null,
        ]);

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Public dashboard link revoked successfully.');
    }
}
