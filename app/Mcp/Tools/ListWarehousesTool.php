<?php

namespace App\Mcp\Tools;

use App\Models\Warehouse;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_warehouses')]
#[Title('List Warehouses')]
#[Description('List and search warehouses. Supports wildcard search and filtering by project_id. Read-only.')]
class ListWarehousesTool extends Tool
{
    public function description(): string
    {
        return 'List and search warehouses with optional wildcard name search and project filtering.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Wildcard search for warehouse name or type'),
            'project_id' => $schema->integer()->description('Filter warehouses by project ID'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => 'nullable|string',
            'project_id' => 'nullable|integer',
        ]);

        $query = Warehouse::query()->with(['project:id,name', 'allocations:id,warehouse_id,name']);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%");
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->get('project_id'));
        }

        $warehouses = $query->get();

        return Response::json($warehouses);
    }
}
