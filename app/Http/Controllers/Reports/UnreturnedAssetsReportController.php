<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UnreturnedAssetsReportController extends Controller
{
    /**
     * Display the Unreturned Assets report.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $itemTypeId = $request->input('item_type_id');
        $search = $request->input('search');

        $warehouses = Warehouse::orderBy('name')->get();
        $itemTypes = ItemType::where('base_behavior', 'ASSET')->orderBy('name')->get();

        $items = $this->getUnreturnedAssets($warehouseId, $itemTypeId, $search);

        return view('reports.unreturned-assets.index', [
            'items' => $items,
            'warehouses' => $warehouses,
            'itemTypes' => $itemTypes,
            'selectedWarehouse' => $warehouseId,
            'selectedItemType' => $itemTypeId,
            'search' => $search,
        ]);
    }

    /**
     * Print view for the Unreturned Assets report.
     */
    public function print(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        $itemTypeId = $request->input('item_type_id');
        $search = $request->input('search');

        $items = $this->getUnreturnedAssets($warehouseId, $itemTypeId, $search);
        $warehouseName = $warehouseId ? Warehouse::find($warehouseId)?->name : 'All Warehouses';

        return view('reports.unreturned-assets.print', [
            'items' => $items,
            'warehouseName' => $warehouseName,
        ]);
    }

    /**
     * Retrieve unreturned assets query results.
     */
    protected function getUnreturnedAssets($warehouseId = null, $itemTypeId = null, $search = null)
    {
        $query = Item::query()
            ->whereHas('itemType', function ($q) {
                $q->where('base_behavior', 'ASSET');
            })
            ->whereNull('current_warehouse_id')
            ->whereHas('ledgers', function ($q) {
                $q->where('type', 'OUT')
                    ->whereIn('action', ['UTILIZE', 'MAINTENANCE'])
                    ->where('id', function ($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('ledgers as sub_l')
                            ->whereColumn('sub_l.item_id', 'ledgers.item_id')
                            ->whereNull('sub_l.deleted_at');
                    });
            })
            ->with([
                'itemType',
                'ledgers' => function ($q) {
                    $q->where('type', 'OUT')
                        ->whereIn('action', ['UTILIZE', 'MAINTENANCE'])
                        ->where('id', function ($sub) {
                            $sub->selectRaw('MAX(id)')
                                ->from('ledgers as sub_l')
                                ->whereColumn('sub_l.item_id', 'ledgers.item_id')
                                ->whereNull('sub_l.deleted_at');
                        })
                        ->with('warehouse');
                },
            ]);

        if ($itemTypeId) {
            $query->where('item_type_id', $itemTypeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specification', 'like', "%{$search}%");
            });
        }

        if ($warehouseId) {
            $query->whereHas('ledgers', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->where('id', function ($sub) {
                        $sub->selectRaw('MAX(id)')
                            ->from('ledgers as sub_l')
                            ->whereColumn('sub_l.item_id', 'ledgers.item_id')
                            ->whereNull('sub_l.deleted_at');
                    });
            });
        }

        return $query->get()->map(function ($item) {
            $lastLedger = $item->ledgers->first();
            $entryDate = $lastLedger ? $lastLedger->entry_date : null;

            $daysOut = 0;
            if ($entryDate) {
                $outDate = Carbon::parse($entryDate)->startOfDay();
                $today = Carbon::today();
                $daysOut = $outDate->lte($today) ? $outDate->diffInDays($today) : 0;
            }

            return (object) [
                'id' => $item->id,
                'name' => $item->name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'item_type' => $item->itemType ? $item->itemType->name : '-',
                'action' => $lastLedger ? $lastLedger->action : '-',
                'out_date' => $entryDate ? Carbon::parse($entryDate)->format('Y-m-d') : '-',
                'warehouse' => $lastLedger && $lastLedger->warehouse ? $lastLedger->warehouse->name : '-',
                'assigned_to' => $lastLedger ? $lastLedger->assigned_to : '-',
                'plate_no' => $lastLedger ? $lastLedger->plate_no : '-',
                'remarks' => $lastLedger ? $lastLedger->remarks : '-',
                'days_out' => (int) $daysOut,
            ];
        })->sortByDesc('days_out')->values();
    }
}
