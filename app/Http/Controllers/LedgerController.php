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
        $warehouses = Warehouse::active()->get();
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
                    $q->where('type', $request->item_type);
                });
            }

            $itemIds = $itemIdsQuery->distinct()->pluck('item_id');

            $items = Item::whereIn('id', $itemIds)->get();

            foreach ($items as $item) {
                $balance = $item->getBalance($selectedWarehouseId);
                if ($balance > 0) {
                    $item->balance = $balance;
                    $itemsWithStock->push($item);
                }
            }

            $query = Ledger::with(['item', 'warehouse']);
            $query->where('warehouse_id', $selectedWarehouseId);

            if ($request->filled('item_type')) {
                $query->whereHas('item', function ($q) use ($request) {
                    $q->where('type', $request->item_type);
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
                $ledgers = $query->oldest('entry_date')
                    ->oldest('created_at')
                    ->paginate(50);

                $currentPage = $ledgers->currentPage();
                $perPage = $ledgers->perPage();

                // Calculate opening balance for the page
                $openingBalance = Ledger::where('warehouse_id', $selectedWarehouseId)
                    ->where('item_id', $request->item_id)
                    ->where(function ($q) use ($request) {
                        if ($request->has('start_date') && $request->start_date) {
                            $q->whereDate('entry_date', '>=', $request->start_date);
                        }
                        if ($request->has('end_date') && $request->end_date) {
                            $q->whereDate('entry_date', '<=', $request->end_date);
                        }
                        if ($request->filled('allocation_id')) {
                            $q->where('allocation_id', $request->allocation_id);
                        }
                    })
                    ->oldest('entry_date')
                    ->oldest('created_at')
                    ->take(($currentPage - 1) * $perPage)
                    ->get()
                    ->reduce(function ($carry, $ledger) {
                        return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
                    }, 0);
            } else {
                $ledgers = $query->latest('entry_date')
                    ->latest('created_at')
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
        if (auth()->user()->isSupervisor()) {
            abort(403, 'Supervisors are not allowed to add entries.');
        }

        $selectedWarehouseId = $request->warehouse_id;
        $selectedItemId = $request->item_id;
        $items = Item::all();
        $warehouses = Warehouse::active()->get();
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
        if (auth()->user()->isSupervisor()) {
            abort(403, 'Supervisors are not allowed to add entries.');
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

        $ledgers = $query->oldest('entry_date')
            ->oldest('created_at')
            ->paginate(50);

        $currentPage = $ledgers->currentPage();
        $perPage = $ledgers->perPage();

        // Calculate opening balance for the page
        $openingBalance = Ledger::where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->where(function ($q) use ($request) {
                if ($request->has('start_date') && $request->start_date) {
                    $q->whereDate('entry_date', '>=', $request->start_date);
                }
                if ($request->has('end_date') && $request->end_date) {
                    $q->whereDate('entry_date', '<=', $request->end_date);
                }
                if ($request->filled('allocation_id')) {
                    $q->where('allocation_id', $request->allocation_id);
                }
            })
            ->oldest('entry_date')
            ->oldest('created_at')
            ->take(($currentPage - 1) * $perPage)
            ->get()
            ->reduce(function ($carry, $ledger) {
                return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
            }, 0);

        $balance = $item->getBalance($warehouse->id);
        $allocations = Allocation::where('warehouse_id', $warehouse->id)->orderBy('name', 'asc')->get();

        return view($this->getRoleView('item_history'), compact('ledgers', 'item', 'warehouse', 'balance', 'openingBalance', 'allocations'));
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

        $ledgers = $query->oldest('entry_date')
            ->oldest('created_at')
            ->get();

        $balance = $item->getBalance($warehouse->id);

        // For print, we calculate opening balance based on the full range up to the first shown record
        $openingBalance = 0;
        if ($request->filled('start_date') || $request->filled('end_date') || $request->filled('allocation_id')) {
            $openingBalance = Ledger::where('warehouse_id', $warehouse->id)
                ->where('item_id', $item->id)
                ->where(function ($q) use ($request) {
                    if ($request->has('start_date') && $request->start_date) {
                        $q->whereDate('entry_date', '<', $request->start_date);
                    }
                    // Note: If no start date but other filters exist, we might need a more complex "history" calc,
                    // but usually print follows what's on screen.
                })
                ->oldest('entry_date')
                ->oldest('created_at')
                ->get()
                ->reduce(function ($carry, $ledger) {
                    return $ledger->type === 'IN' ? $carry + $ledger->quantity : $carry - $ledger->quantity;
                }, 0);
        }

        return view($this->getRoleView('print_item_history'), compact('ledgers', 'item', 'warehouse', 'balance', 'openingBalance'));
    }

    public function show(Ledger $ledger)
    {
        $ledger->load(['item', 'warehouse', 'project', 'allocation', 'sourceWarehouse', 'destinationWarehouse', 'linkedLedger']);

        return view($this->getRoleView('show'), compact('ledger'));
    }

    private function getRoleView($viewName)
    {
        $role = auth()->user()->role;

        return "{$role}.ledgers.{$viewName}";
    }

    public function approve(Ledger $ledger)
    {
        $ledger->update([
            'status' => 'APPROVED',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ledger entry approved.');
    }
}
