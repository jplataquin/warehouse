<?php

namespace App\Mcp\Tools;

use App\Models\Item;
use App\Models\ItemType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_items')]
#[Title('List Items')]
#[Description('List and search materials/items with item types having the base behavior of type "Bulk". Supports wildcard search filter by name and filter by item type. Read-only.')]
class ListItemsTool extends Tool
{
    public function description(): string
    {
        return 'List materials/items with item types that have the base behavior of type "Bulk". Supports wildcard search filter by name and filter by item type.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $bulkTypes = [];
        try {
            $bulkTypes = ItemType::where('base_behavior', 'BULK')->pluck('name')->toArray();
        } catch (\Throwable $e) {
            // Table might not be available in some contexts
        }

        $itemTypeField = $schema->string()->description('Filter bulk items by item type name');
        if (!empty($bulkTypes)) {
            $itemTypeField->enum($bulkTypes);
        }

        return [
            'name' => $schema->string()->description('Wildcard search term for item name'),
            'item_type' => $itemTypeField,
            'search' => $schema->string()->description('Optional wildcard search term for item name or specification'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'name' => 'nullable|string',
            'item_type' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        $query = Item::query()
            ->whereHas('itemType', function ($q) {
                $q->where('base_behavior', 'BULK');
            })
            ->with(['itemType:id,name']);

        if ($request->filled('item_type')) {
            $itemType = $request->get('item_type');
            $query->whereHas('itemType', function ($q) use ($itemType) {
                $q->where('base_behavior', 'BULK')
                    ->where(function ($sub) use ($itemType) {
                        $sub->where('name', $itemType)
                            ->orWhere('id', $itemType);
                    });
            });
        }

        if ($request->filled('name')) {
            $nameTerm = $request->get('name');
            $query->where('name', 'like', "%{$nameTerm}%");
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('specification', 'like', "%{$term}%");
            });
        }

        $items = $query->get();

        return Response::json($items);
    }
}
