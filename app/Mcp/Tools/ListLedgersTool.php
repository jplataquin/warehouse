<?php

namespace App\Mcp\Tools;

use App\Models\Ledger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('list_ledgers')]
#[Title('List Ledgers')]
#[Description('List and filter ledger entries for a specific warehouse. Requires warehouse_id. Read-only.')]
class ListLedgersTool extends Tool
{
    public function description(): string
    {
        return 'List and filter ledger entries for a specific warehouse.';
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'warehouse_id' => $schema->integer()->description('The ID of the warehouse (required)')->required(),
            'entry_date_from' => $schema->string()->description('Filter entries on or after this date (YYYY-MM-DD)'),
            'entry_date_to' => $schema->string()->description('Filter entries on or before this date (YYYY-MM-DD)'),
            'created_by' => $schema->integer()->description('Filter entries created by this user ID'),
            'item_id' => $schema->integer()->description('Filter entries for this specific item ID'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'entry_date_from' => 'nullable|date',
            'entry_date_to' => 'nullable|date',
            'created_by' => 'nullable|integer',
            'item_id' => 'nullable|integer',
        ]);

        $query = Ledger::query()
            ->where('warehouse_id', $request->get('warehouse_id'))
            ->with(['item:id,name,specification,unit', 'creator:id,name']);

        if ($request->filled('entry_date_from')) {
            $query->whereDate('entry_date', '>=', $request->get('entry_date_from'));
        }

        if ($request->filled('entry_date_to')) {
            $query->whereDate('entry_date', '<=', $request->get('entry_date_to'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->get('created_by'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        $ledgers = $query->latest('entry_date')->latest('id')->limit(100)->get();

        return Response::json($ledgers);
    }
}
