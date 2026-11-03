<?php

namespace App\Mcp\Tools;

use App\Models\ItemType;
use App\Models\Ledger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_stock_levels')]
#[Title('List Stock Levels')]
#[Description('List aggregated stock levels per item for a specific warehouse. Supports filtering by item ID, search term, date, item type, and base behavior. Requires warehouse_id. Read-only.')]
class ListStockLevelsTool extends Tool
{
    public function description(): string
    {
        return 'List aggregated stock levels per item for a specific warehouse.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $allTypes = [];
        try {
            $allTypes = ItemType::pluck('name')->toArray();
        } catch (\Throwable $e) {
            // Table might not be available in some contexts
        }

        $itemTypeField = $schema->string()->description('Filter stock by item type name');
        if (! empty($allTypes)) {
            $itemTypeField->enum($allTypes);
        }

        return [
            'warehouse_id' => $schema->integer()->description('The ID of the warehouse (required)')->required(),
            'item_id' => $schema->integer()->description('Filter by specific item ID'),
            'search' => $schema->string()->description('Wildcard search term for item name or specification'),
            'as_of_date' => $schema->string()->description('Calculate stock up to this date (YYYY-MM-DD)'),
            'item_type' => $itemTypeField,
            'base_behavior' => $schema->string()
                ->enum(['BULK', 'ASSET'])
                ->description('Filter stock by base behavior: BULK or ASSET'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'item_id' => 'nullable|integer',
            'search' => 'nullable|string',
            'as_of_date' => 'nullable|date',
            'item_type' => 'nullable|string',
            'base_behavior' => 'nullable|string',
        ]);

        $query = Ledger::query()
            ->where('warehouse_id', $request->get('warehouse_id'))
            ->whereHas('item')
            ->selectRaw("
                item_id,
                SUM(CASE WHEN type = 'IN' THEN quantity ELSE -quantity END) as current_stock,
                MAX(entry_date) as last_entry_date
            ")
            ->groupBy('item_id')
            ->with(['item.itemType']);

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->whereHas('item', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('specification', 'like', "%{$term}%");
                });
            });
        }

        if ($request->filled('as_of_date')) {
            $query->whereDate('entry_date', '<=', $request->get('as_of_date'));
        }

        if ($request->filled('item_type')) {
            $itemType = $request->get('item_type');
            $query->whereHas('item.itemType', function ($q) use ($itemType) {
                $q->where(function ($sub) use ($itemType) {
                    $sub->where('name', $itemType)
                        ->orWhere('id', $itemType);
                });
            });
        }

        if ($request->filled('base_behavior')) {
            $behavior = strtoupper($request->get('base_behavior'));
            $query->whereHas('item.itemType', function ($q) use ($behavior) {
                $q->where('base_behavior', $behavior);
            });
        }

        $items = $query->get()->map(function ($row) {
            $item = $row->item;
            $lastEntryDate = null;
            if (! empty($row->last_entry_date)) {
                $lastEntryDate = is_string($row->last_entry_date)
                    ? substr($row->last_entry_date, 0, 10)
                    : (is_object($row->last_entry_date) && method_exists($row->last_entry_date, 'format')
                        ? $row->last_entry_date->format('Y-m-d')
                        : (string) $row->last_entry_date);
            }

            return [
                'item_id' => (int) $row->item_id,
                'name' => $item ? $item->name : null,
                'specification' => $item ? $item->specification : null,
                'unit' => $item ? $item->unit : null,
                'item_type' => $item && $item->itemType ? $item->itemType->name : null,
                'base_behavior' => $item && $item->itemType ? $item->itemType->base_behavior : null,
                'current_stock' => number_format((float) $row->current_stock, 2, '.', ''),
                'last_entry_date' => $lastEntryDate,
            ];
        })->values();

        return Response::json($items);
    }
}
