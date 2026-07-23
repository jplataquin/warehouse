<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class LoggerAssignmentController extends Controller
{
    public function index()
    {
        $loggers = User::whereIn('role', ['logger', 'viewer'])->with('warehouses')->get();
        $warehouses = Warehouse::all();

        return view('supervisor.assignments.index', compact('loggers', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'warehouse_ids' => 'nullable|array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (!in_array($user->role, ['logger', 'viewer'])) {
            return back()->with('error', 'Assignments can only be made to users with logger or viewer roles.');
        }

        $user->warehouses()->sync($validated['warehouse_ids'] ?? []);

        return redirect()->route('assignments.index')->with('success', "Warehouses assigned to {$user->name} successfully.");
    }
}
