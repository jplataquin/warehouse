<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class LoggerAssignmentController extends Controller
{
    public function edit(User $user)
    {
        if (!in_array($user->role, ['logger', 'viewer'])) {
            abort(404, 'User is not a logger or viewer.');
        }

        $warehouses = Warehouse::active()->get();

        return view('supervisor.assignments.edit', compact('user', 'warehouses'));
    }

    public function update(Request $request, User $user)
    {
        if (!in_array($user->role, ['logger', 'viewer'])) {
            abort(404, 'User is not a logger or viewer.');
        }

        $validated = $request->validate([
            'warehouse_ids' => 'nullable|array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);

        $user->warehouses()->sync($validated['warehouse_ids'] ?? []);

        return redirect()->route('users.index')->with('success', "Warehouses assigned to {$user->name} successfully.");
    }
}
