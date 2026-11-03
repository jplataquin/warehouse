<?php

namespace App\Mcp\Tools;

use App\Models\Item;
use App\Models\ItemType;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_unreturned_assets')]
#[Title('List Unreturned Assets')]
#[Description('List all asset items whose last ledger entry is OUT UTILIZE or OUT MAINTENANCE, along with the origin warehouse and days out. Read-only.')]
class ListUnreturnedAssetsTool extends Tool
{
    public function description(): string
    {
        return 'List all assets currently logged out for utilization or maintenance, with origin warehouse and calculated days out.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $assetTypes = [];
        try {
            $assetTypes = ItemType::where('base_behavior', 'ASSET')->pluck('name')->toArray();
        } catch (\Throwable $e) {
            // Table might not be available in some contexts
        }

        $itemTypeField = $schema->string()->description('Filter assets by asset type name');
        if (! empty($assetTypes)) {
            $itemTypeField->enum($assetTypes);
        }

        return [
            'warehouse_id' => $schema->integer()->description('Filter unreturned assets by origin warehouse ID'),
            'item_type' => $itemTypeField,
            'search' => $schema->string()->description('Wildcard search term for asset name or specification'),
            'min_days_out' => $schema->integer()->description('Filter assets that have been out for at least this many days'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'warehouse_id' => 'nullable|integer',
            'item_type' => 'nullable|string',
            'search' => 'nullable|string',
            'min_days_out' => 'nullable|integer|min:0',
        ]);

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

        if ($request->filled('item_type')) {
            $itemType = $request->get('item_type');
            $query->whereHas('itemType', function ($q) use ($itemType) {
                $q->where('name', $itemType)
                    ->orWhere('id', $itemType);
            });
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('specification', 'like', "%{$term}%");
            });
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = $request->get('warehouse_id');
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

        $minDaysOut = $request->get('min_days_out');

        $results = $query->get()->map(function ($item) {
            $lastLedger = $item->ledgers->first();
            $entryDate = $lastLedger ? $lastLedger->entry_date : null;

            $daysOut = 0;
            if ($entryDate) {
                $outDate = Carbon::parse($entryDate)->startOfDay();
                $today = Carbon::today();
                $daysOut = $outDate->lte($today) ? $outDate->diffInDays($today) : 0;
            }

            return [
                'item_id' => (int) $item->id,
                'name' => $item->name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'item_type' => $item->itemType ? $item->itemType->name : null,
                'action' => $lastLedger ? $lastLedger->action : null,
                'origin_warehouse_id' => $lastLedger ? (int) $lastLedger->warehouse_id : null,
                'origin_warehouse' => $lastLedger && $lastLedger->warehouse ? $lastLedger->warehouse->name : null,
                'out_date' => $entryDate ? Carbon::parse($entryDate)->format('Y-m-d') : null,
                'assigned_to' => $lastLedger ? $lastLedger->assigned_to : null,
                'plate_no' => $lastLedger ? $lastLedger->plate_no : null,
                'remarks' => $lastLedger ? $lastLedger->remarks : null,
                'days_out' => (int) $daysOut,
            ];
        });

        if ($minDaysOut !== null) {
            $results = $results->filter(fn ($item) => $item['days_out'] >= (int) $minDaysOut);
        }

        return Response::json($results->sortByDesc('days_out')->values());
    }
}
