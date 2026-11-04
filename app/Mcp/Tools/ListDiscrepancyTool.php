<?php

namespace App\Mcp\Tools;

use App\Models\LedgerDiscrepancy;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_discrepancy')]
#[Title('List Discrepancies')]
#[Description('List and filter logged ledger discrepancies. Supports filtering by warehouse_id, item_id, status (DISCREPANCY, RESOLVED), resolved_by, and date range. Read-only.')]
class ListDiscrepancyTool extends Tool
{
    public function description(): string
    {
        return 'List and filter logged ledger discrepancies with optional warehouse, item, status, resolver, and date filters.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'warehouse_id' => $schema->integer()->description('Filter discrepancies for a specific warehouse ID'),
            'item_id' => $schema->integer()->description('Filter discrepancies for a specific item ID'),
            'status' => $schema->string()
                ->enum(['DISCREPANCY', 'RESOLVED'])
                ->description('Filter by discrepancy status: DISCREPANCY or RESOLVED'),
            'resolved_by' => $schema->integer()->description('Filter discrepancies resolved by a specific user ID'),
            'created_from' => $schema->string()->description('Filter discrepancies logged on or after this date (YYYY-MM-DD)'),
            'created_to' => $schema->string()->description('Filter discrepancies logged on or before this date (YYYY-MM-DD)'),
            'limit' => $schema->integer()->description('Maximum number of records to return (1-100, default 50)'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'item_id' => 'nullable|integer|exists:items,id',
            'status' => 'nullable|string|in:DISCREPANCY,RESOLVED,discrepancy,resolved',
            'resolved_by' => 'nullable|integer|exists:users,id',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = LedgerDiscrepancy::query()
            ->with([
                'warehouse:id,name,type',
                'item:id,name,specification,unit,type',
                'ledger:id,entry_date,type,action,quantity',
                'resolver:id,name',
            ]);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->get('status')));
        }

        if ($request->filled('resolved_by')) {
            $query->where('resolved_by', $request->get('resolved_by'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }

        $limit = (int) $request->get('limit', 50);
        $discrepancies = $query->latest('id')->limit($limit)->get();

        return Response::json($discrepancies);
    }
}
