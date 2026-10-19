<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\Warehouse;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class LedgerController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request)
    {
        $warehouses = Warehouse::active()->with('parent')->get();
        $selectedWarehouseId = $request->warehouse_id;
        $selectedWarehouse = null;
        $itemsWithStock = collect();

        if ($selectedWarehouseId) {
            $selectedWarehouse = Warehouse::active()->find($selectedWarehouseId);

            if (! $selectedWarehouse) {
                return redirect()->route('ledgers.index')->with('error', 'The selected warehouse is inactive or does not exist.');
            }

            // Get items that have ledger entries in this warehouse
            $itemIdsQuery = Ledger::where('warehouse_id', $selectedWarehouseId);

            if ($request->filled('item_type')) {
                $itemIdsQuery->whereHas('item', function ($q) use ($request) {
                    $itemType = $request->item_type;
                    if (is_numeric($itemType)) {
                        $q->where('item_type_id', $itemType);
                    } else {
                        $q->whereHas('itemType', function ($sub) use ($itemType) {
                            $sub->where('base_behavior', $itemType);
                        });
                    }
                });
            }

            $itemIds = $itemIdsQuery->distinct()->pluck('item_id');

            $itemsQuery = Item::whereIn('id', $itemIds);

            if ($request->filled('item_search')) {
                $itemSearch = $request->item_search;
                $itemsQuery->where(function ($q) use ($itemSearch) {
                    $q->where('name', 'LIKE', "%{$itemSearch}%")
                        ->orWhere('specification', 'LIKE', "%{$itemSearch}%");
                });
            }

            $items = $itemsQuery->get();

            foreach ($items as $item) {
                $item->balance = $item->getBalance($selectedWarehouseId);
                $itemsWithStock->push($item);
            }

            $query = Ledger::with(['item', 'warehouse']);
            $query->where('warehouse_id', $selectedWarehouseId);

            if ($request->filled('item_type')) {
                $query->whereHas('item', function ($q) use ($request) {
                    $itemType = $request->item_type;
                    if (is_numeric($itemType)) {
                        $q->where('item_type_id', $itemType);
                    } else {
                        $q->whereHas('itemType', function ($sub) use ($itemType) {
                            $sub->where('base_behavior', $itemType);
                        });
                    }
                });
            }

            if ($request->has('item_id') && $request->item_id) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('entry_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('entry_date', '<=', $request->end_date);
            }

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            if ($request->has('action') && $request->action) {
                $query->where('action', $request->action);
            }

            if ($request->filled('allocation_id')) {
                $query->where('allocation_id', $request->allocation_id);
            }

            // Running balance logic only if single item selected
            $openingBalance = 0;
            if ($request->filled('item_id')) {
                $baseQuery = clone $query;
                $ledgers = $query->latest('entry_date')
                    ->latest('created_at')
                    ->latest('id')
                    ->paginate(50);

                $openingBalance = $this->attachRunningBalances($baseQuery, $ledgers, $selectedWarehouseId, $request->item_id, $request, true);
            } else {
                $ledgers = $query->latest('entry_date')
                    ->latest('created_at')
                    ->latest('id')
                    ->paginate(50);
            }

            $item = null;
            $balance = 0;
            if ($request->has('item_id') && $request->item_id) {
                $item = Item::find($request->item_id);
                if ($item) {
                    $balance = $item->getBalance($selectedWarehouseId);
                }
            }

            $allocations = Allocation::where('warehouse_id', $selectedWarehouseId)->orderBy('name', 'asc')->get();
        } else {
            // Return empty pagination if no warehouse selected
            $ledgers = new LengthAwarePaginator([], 0, 50);
            $item = null;
            $balance = 0;
            $openingBalance = 0;
            $allocations = collect();
        }

        return view($this->getRoleView('index'), compact('ledgers', 'item', 'balance', 'warehouses', 'selectedWarehouse', 'itemsWithStock', 'openingBalance', 'allocations'));
    }

    public function create(Request $request)
    {
        if (auth()->user()->isSupervisor() || auth()->user()->isViewer()) {
            abort(403, 'Unauthorized action.');
        }

        $selectedWarehouseId = $request->warehouse_id;
        $selectedItemId = $request->item_id;
        $items = Item::all();
        $warehouses = Warehouse::active()->with('parent')->get();
        $allocations = Allocation::orderBy('name', 'asc')->get();
        $projects = Project::all();

        $warehouse = null;
        if ($selectedWarehouseId) {
            $warehouse = Warehouse::find($selectedWarehouseId);
        }

        return view($this->getRoleView('create'), compact('items', 'warehouses', 'allocations', 'selectedWarehouseId', 'selectedItemId', 'warehouse', 'projects'));
    }

    public function getAllocationsByWarehouse(Request $request)
    {
        $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        $allocations = Allocation::where('warehouse_id', $request->warehouse_id)->orderBy('name', 'asc')->get(['id', 'name']);

        return response()->json($allocations);
    }

    public function store(Request $request)
    {
        if (auth()->user()->isSupervisor() || auth()->user()->isViewer()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.entry_date' => 'required|date|before_or_equal:today',
            'entries.*.type' => 'required|in:IN,OUT',
            'entries.*.action' => 'required|in:TRANSFER,DELIVERY,ASSET_RETURN,ALLOCATE,DISPOSE,LOST,REJECT,MAINTENANCE,CORRECTION,INITIAL_STOCK,UTILIZE',
            'entries.*.item_id' => 'required|exists:items,id',
            'entries.*.quantity' => 'required|numeric|min:0.01',
            'entries.*.warehouse_id' => 'required|exists:warehouses,id',
            'entries.*.project_id' => 'nullable|exists:projects,id',
            'entries.*.destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'entries.*.source_warehouse_id' => 'nullable|exists:warehouses,id',
            'entries.*.allocation_id' => 'nullable|exists:allocations,id',
            'entries.*.po_number' => 'nullable|string',
            'entries.*.offical_receipt' => 'nullable|string',
            'entries.*.delivery_receipt' => 'nullable|string',
            'entries.*.assigned_to' => 'nullable|string',
            'entries.*.plate_no' => 'nullable|string',
            'entries.*.remarks' => 'nullable|string',
        ]);

        try {
            $warehouseId = null;
            foreach ($validated['entries'] as $entry) {
                if ($entry['action'] === 'CORRECTION' && ! auth()->user()->isAdmin()) {
                    abort(403, 'Only admins can perform corrections.');
                }
                $this->ledgerService->createEntry($entry);
                $warehouseId = $entry['warehouse_id'];
            }

            $redirectUrl = $warehouseId
                ? route('logger.warehouse.dashboard', $warehouseId)
                : route('ledgers.index');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ledger entries created successfully.',
                    'redirect' => $redirectUrl,
                ]);
            }

            return redirect()->to($redirectUrl)->with('success', 'Ledger entries created successfully.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', Arr::flatten($e->errors())),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function itemHistory($warehouseId, $itemId, Request $request)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $item = Item::findOrFail($itemId);

        $query = Ledger::with(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse'])
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id);

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('entry_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('entry_date', '<=', $request->end_date);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->filled('allocation_id')) {
            $query->where('allocation_id', $request->allocation_id);
        }

        $baseQuery = clone $query;
        $ledgers = $query->latest('entry_date')
            ->latest('created_at')
            ->latest('id')
            ->paginate(50);

        $openingBalance = $this->attachRunningBalances($baseQuery, $ledgers, $warehouse->id, $item->id, $request, true);

        $balance = $item->getBalance($warehouse->id);
        $allocations = Allocation::where('warehouse_id', $warehouse->id)->orderBy('name', 'asc')->get();

        // Check stock threshold
        $thresholdRegistry = \App\Models\StockLevelRegistry::where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->first();

        $lowStockAlert = null;
        if ($thresholdRegistry && $balance < $thresholdRegistry->threshold) {
            $lowStockAlert = [
                'item_name' => $item->name,
                'warehouse_name' => $warehouse->name,
                'current_stock' => $balance,
                'threshold' => $thresholdRegistry->threshold,
                'unit' => $item->unit,
            ];
        }

        return view($this->getRoleView('item_history'), compact('ledgers', 'item', 'warehouse', 'balance', 'openingBalance', 'allocations', 'lowStockAlert'));
    }

    public function printItemHistory($warehouseId, $itemId, Request $request)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $item = Item::findOrFail($itemId);

        $query = Ledger::with(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse'])
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id);

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('entry_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('entry_date', '<=', $request->end_date);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->filled('allocation_id')) {
            $query->where('allocation_id', $request->allocation_id);
        }

        $baseQuery = clone $query;
        $ledgers = $query->latest('entry_date')
            ->latest('created_at')
            ->latest('id')
            ->get();

        $balance = $item->getBalance($warehouse->id);

        $openingBalance = $this->attachRunningBalances($baseQuery, $ledgers, $warehouse->id, $item->id, $request, false);

        return view($this->getRoleView('print_item_history'), compact('ledgers', 'item', 'warehouse', 'balance', 'openingBalance'));
    }

    /**
     * Compute and attach running balances for a paginated or collected set of ledgers in descending order.
     */
    private function attachRunningBalances($query, $ledgers, $warehouseId, $itemId, Request $request, bool $isPaginated = true): float
    {
        // 1. Balance prior to start_date if start_date filter is present
        $priorBalance = 0;
        if ($request->filled('start_date')) {
            $priorBalance = Ledger::where('warehouse_id', $warehouseId)
                ->where('item_id', $itemId)
                ->whereDate('entry_date', '<', $request->start_date)
                ->when($request->filled('allocation_id'), function ($q) use ($request) {
                    $q->where('allocation_id', $request->allocation_id);
                })
                ->get(['type', 'quantity'])
                ->reduce(function ($carry, $ledger) {
                    return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
                }, 0);
        }

        // 2. Sum of all matching records in the filtered query
        $filteredSum = (clone $query)
            ->without(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse', 'linkedLedger'])
            ->get(['type', 'quantity'])
            ->reduce(function ($carry, $ledger) {
                return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
            }, 0);

        $totalBalance = (float) $priorBalance + (float) $filteredSum;

        // 3. For paginated results, deduct movements of records newer than this page (skipped records)
        $skippedCount = 0;
        if ($isPaginated && $ledgers instanceof LengthAwarePaginator) {
            $skippedCount = ($ledgers->currentPage() - 1) * $ledgers->perPage();
        }

        $skippedSum = 0;
        if ($skippedCount > 0) {
            $skippedSum = (clone $query)
                ->without(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse', 'linkedLedger'])
                ->latest('entry_date')
                ->latest('created_at')
                ->latest('id')
                ->take($skippedCount)
                ->get(['type', 'quantity'])
                ->reduce(function ($carry, $ledger) {
                    return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
                }, 0);
        }

        $pageStartingBalance = (float) $totalBalance - (float) $skippedSum;

        // 4. Attach running_balance to each ledger entry on this page
        $currentBal = $pageStartingBalance;
        foreach ($ledgers as $ledger) {
            $ledger->running_balance = $currentBal;
            $currentBal -= ($ledger->type === 'IN' ? $ledger->quantity : -$ledger->quantity);
        }

        return (float) $pageStartingBalance;
    }

    public function show(Ledger $ledger)
    {
        $ledger->load(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse', 'linkedLedger']);

        return view($this->getRoleView('show'), compact('ledger'));
    }

    private function getRoleView($viewName)
    {
        $role = auth()->user()->role;
        if ($role === 'viewer') {
            $role = 'logger';
        }

        return "{$role}.ledgers.{$viewName}";
    }

    public function edit($ledger)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to edit ledger entries.');
        }

        if (! ($ledger instanceof Ledger) || ! $ledger->exists) {
            $ledger = Ledger::findOrFail(is_numeric($ledger) ? $ledger : $ledger->id);
        }

        $items = Item::all();
        $warehouses = Warehouse::active()->with('parent')->get();
        // Get allocations for the current warehouse of the ledger
        $allocations = Allocation::where('warehouse_id', $ledger->warehouse_id)->orderBy('name', 'asc')->get();
        $projects = Project::all();

        return view('admin.ledgers.edit', compact('ledger', 'items', 'warehouses', 'allocations', 'projects'));
    }

    public function update(Request $request, $ledger)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to edit ledger entries.');
        }

        if (! ($ledger instanceof Ledger) || ! $ledger->exists) {
            $ledger = Ledger::findOrFail(is_numeric($ledger) ? $ledger : $ledger->id);
        }

        $validated = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'type' => 'required|in:IN,OUT',
            'action' => 'required|in:TRANSFER,DELIVERY,ASSET_RETURN,ALLOCATE,DISPOSE,LOST,REJECT,MAINTENANCE,CORRECTION,INITIAL_STOCK,UTILIZE',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'warehouse_id' => 'required|exists:warehouses,id',
            'project_id' => 'nullable|exists:projects,id',
            'destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'source_warehouse_id' => 'nullable|exists:warehouses,id',
            'allocation_id' => 'nullable|exists:allocations,id',
            'po_number' => 'nullable|string',
            'offical_receipt' => 'nullable|string',
            'delivery_receipt' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'plate_no' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            $this->ledgerService->updateEntry($ledger, $validated);

            return redirect()->route('ledgers.show', $ledger)->with('success', 'Ledger entry updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approve(Ledger $ledger)
    {
        $ledger->update([
            'status' => 'APPROVED',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ledger entry approved.');
    }

    public function destroy(Request $request, $ledger)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to delete ledger entries.');
        }

        if (! ($ledger instanceof Ledger) || ! $ledger->exists) {
            $ledger = Ledger::findOrFail(is_numeric($ledger) ? $ledger : $ledger->id);
        }

        $validated = $request->validate([
            'delete_reason' => 'required|string|min:5|max:255',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($ledger, $validated) {
                $item = \App\Models\Item::lockForUpdate()->findOrFail($ledger->item_id);

                $ledger->update([
                    'delete_reason' => $validated['delete_reason'],
                    'deleted_by' => auth()->id(),
                ]);

                $ledger->delete();

                // Check post-deletion balance (bypassed for Admins)
                if (! auth()->user()->isAdmin() && $item->getBalance($ledger->warehouse_id) < 0) {
                    throw new \Exception("Cannot delete this entry because it would result in a negative stock balance for '{$item->name}'.");
                }
            });

            return redirect()->route('ledgers.item_history', ['warehouse' => $ledger->warehouse_id, 'item' => $ledger->item_id])
                ->with('success', 'Ledger entry soft deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins are allowed to bulk delete ledger entries.');
        }

        $validated = $request->validate([
            'ledger_ids' => 'required|array|min:1',
            'ledger_ids.*' => 'exists:ledgers,id',
            'delete_reason' => 'required|string|min:5|max:255',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
                foreach ($validated['ledger_ids'] as $id) {
                    $ledger = Ledger::findOrFail($id);
                    $item = \App\Models\Item::lockForUpdate()->findOrFail($ledger->item_id);

                    $ledger->update([
                        'delete_reason' => $validated['delete_reason'],
                        'deleted_by' => auth()->id(),
                    ]);

                    $ledger->delete();

                    // Check post-deletion balance (bypassed for Admins)
                    if (! auth()->user()->isAdmin() && $item->getBalance($ledger->warehouse_id) < 0) {
                        throw new \Exception("Cannot delete entry #{$ledger->id} because it would result in a negative stock balance for '{$item->name}'.");
                    }
                }
            });

            // Find the warehouse_id and item_id of one of the deleted entries to redirect to the correct item history page
            $firstLedger = Ledger::withTrashed()->findOrFail($validated['ledger_ids'][0]);

            return redirect()->route('ledgers.item_history', [
                'warehouse' => $firstLedger->warehouse_id,
                'item' => $firstLedger->item_id
            ])->with('success', 'Selected ledger entries soft deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
