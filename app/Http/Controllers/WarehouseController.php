<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $query = Warehouse::with(['project', 'loggers']);

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
        if (!$warehouse instanceof Warehouse) {
            $warehouse = Warehouse::findOrFail($warehouse);
        }

        $warehouse->load(['project', 'loggers', 'allocations']);
        $availableLoggers = User::where('role', 'logger')
            ->whereDoesntHave('warehouses', function ($query) use ($warehouse) {
                $query->where('warehouses.id', $warehouse->id);
            })->get();
            
        return view('supervisor.warehouses.show', compact('warehouse', 'availableLoggers'));
    }

    public function assignLogger(Request $request, $warehouse)
    {
        if (!$warehouse instanceof Warehouse) {
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
        if (!$warehouse instanceof Warehouse) {
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
        return view('supervisor.warehouses.create', compact('projects', 'loggers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'required|in:SITE,CENTRAL',
            'name' => 'required|string|max:255',
            'status' => 'required|in:Active,Deactivated,ACTIVE,DEACTIVATED',
            'logger_ids' => 'nullable|array',
            'logger_ids.*' => 'exists:users,id',
        ]);
        $warehouse = Warehouse::create($validated);
        
        if ($request->has('logger_ids')) {
            $warehouse->loggers()->sync($request->logger_ids);
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        $projects = Project::all();
        $loggers = User::where('role', 'logger')->get();
        return view('supervisor.warehouses.edit', compact('warehouse', 'projects', 'loggers'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'required|in:SITE,CENTRAL',
            'name' => 'required|string|max:255',
            'status' => 'required|in:Active,Deactivated,ACTIVE,DEACTIVATED',
            'logger_ids' => 'nullable|array',
            'logger_ids.*' => 'exists:users,id',
        ]);
        $warehouse->update($validated);
        
        $warehouse->loggers()->sync($request->logger_ids ?? []);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
