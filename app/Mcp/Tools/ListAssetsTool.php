<?php

namespace App\Mcp\Tools;

use App\Models\Item;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List and search asset items. Supports wildcard search and filtering by warehouse or status. Read-only.')]
class ListAssetsTool extends Tool
{
    public function description(): string
    {
        return 'List and search assets with optional wildcard search, warehouse filter, and status filter.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Wildcard search term for asset name or specification'),
            'warehouse_id' => $schema->integer()->description('Filter assets by current warehouse ID'),
            'status' => $schema->string()->description('Filter assets by status (e.g., Operational, Damaged)'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
            'status' => 'nullable|string',
        ]);

        $query = Item::query()
            ->whereHas('itemType', function ($q) {
                $q->where('base_behavior', 'ASSET');
            })
            ->with(['currentWarehouse:id,name', 'itemType:id,name']);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('specification', 'like', "%{$term}%");
            });
        }

        if ($request->filled('warehouse_id')) {
            $query->where('current_warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $assets = $query->get();

        return Response::json($assets);
    }
}
