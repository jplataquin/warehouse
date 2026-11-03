<?php

namespace App\Mcp\Tools;

use App\Models\ItemType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_item_types')]
#[Title('List Item Types')]
#[Description('List all item types maintained in the system. Supports filtering by base behavior (BULK, ASSET). Read-only.')]
class ListItemTypesTool extends Tool
{
    public function description(): string
    {
        return 'List all item types maintained in the system with optional filter for base behavior (BULK or ASSET).';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'base_behavior' => $schema->string()
                ->enum(['BULK', 'ASSET'])
                ->description('Filter item types by base behavior: BULK or ASSET'),
            'search' => $schema->string()->description('Optional wildcard search term for item type name'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'base_behavior' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        $query = ItemType::query()->withCount('items');

        if ($request->filled('base_behavior')) {
            $behavior = strtoupper($request->get('base_behavior'));
            $query->where('base_behavior', $behavior);
        }

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where('name', 'like', "%{$term}%");
        }

        $itemTypes = $query->orderBy('name')->get();

        return Response::json($itemTypes);
    }
}
