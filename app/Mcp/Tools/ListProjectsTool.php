<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_warehouse_projects')]
#[Title('List Warehouse Projects')]
#[Description('List and search projects. Supports wildcard search. Read-only.')]
class ListProjectsTool extends Tool
{
    public function description(): string
    {
        return 'List and search projects with optional wildcard search.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Wildcard search term for project name'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => 'nullable|string',
        ]);

        $query = Project::query()->withCount('warehouses');

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where('name', 'like', "%{$term}%");
        }

        $projects = $query->get();

        return Response::json($projects);
    }
}
