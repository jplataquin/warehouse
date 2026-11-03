<?php

namespace Tests\Feature;

use App\Mcp\Servers\WarehouseServer;
use App\Mcp\Tools\ListAssetsTool;
use App\Mcp\Tools\ListItemTypesTool;
use App\Mcp\Tools\ListItemsTool;
use App\Mcp\Tools\ListLedgersTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListWarehousesTool;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_to_mcp_is_rejected(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ], [
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/list',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_request_with_sanctum_token_is_accepted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/list',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);

        $toolNames = collect($response->json('result.tools'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing([
            'list_ledgers',
            'list_warehouses',
            'list_warehouse_projects',
            'list_assets',
            'list_items',
            'list_item_types',
        ], $toolNames);
    }

    public function test_mcp_tool_can_be_called_via_jsonrpc_using_snake_case_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Project::create(['name' => 'Metro Terminal Construction']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_warehouse_projects',
                'arguments' => ['search' => 'Metro'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertSee('Metro Terminal Construction');
    }

    public function test_all_mcp_tools_are_registered(): void
    {
        WarehouseServer::tools()->assertRegistered([
            ListLedgersTool::class,
            ListWarehousesTool::class,
            ListProjectsTool::class,
            ListAssetsTool::class,
        ]);
    }

    public function test_list_warehouses_tool_with_search_and_project_filter(): void
    {
        $project1 = Project::create(['name' => 'Project Alpha']);
        $project2 = Project::create(['name' => 'Project Beta']);

        $warehouse1 = Warehouse::create([
            'name' => 'Alpha Central Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
            'project_id' => $project1->id,
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'Beta Storage Facility',
            'type' => 'SECONDARY',
            'status' => 'ACTIVE',
            'project_id' => $project2->id,
        ]);

        // Wildcard search test
        WarehouseServer::tool(ListWarehousesTool::class, ['search' => 'Alpha'])
            ->assertOk()
            ->assertSee('Alpha Central Warehouse')
            ->assertDontSee('Beta Storage Facility');

        // Project filter test
        WarehouseServer::tool(ListWarehousesTool::class, ['project_id' => $project2->id])
            ->assertOk()
            ->assertSee('Beta Storage Facility')
            ->assertDontSee('Alpha Central Warehouse');
    }

    public function test_list_projects_tool_with_wildcard_search(): void
    {
        Project::create(['name' => 'Skyline Tower Construction']);
        Project::create(['name' => 'Ocean View Residences']);

        WarehouseServer::tool(ListProjectsTool::class, ['search' => 'Skyline'])
            ->assertOk()
            ->assertSee('Skyline Tower Construction')
            ->assertDontSee('Ocean View Residences');
    }

    public function test_list_assets_tool_only_returns_assets_and_filters(): void
    {
        $assetType = ItemType::create([
            'name' => 'Heavy Machinery',
            'base_behavior' => 'ASSET',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Construction Material',
            'base_behavior' => 'BULK',
        ]);

        $warehouse1 = Warehouse::create([
            'name' => 'North Yard',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'South Depot',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $asset1 = Item::create([
            'name' => 'Excavator Cat 320',
            'specification' => 'Serial 12345',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => $warehouse1->id,
            'status' => 'Operational',
        ]);

        $asset2 = Item::create([
            'name' => 'Bulldozer Komatsu D65',
            'specification' => 'Serial 67890',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => $warehouse2->id,
            'status' => 'Damaged',
        ]);

        $bulkItem = Item::create([
            'name' => 'Portland Cement 50kg',
            'specification' => 'Type 1',
            'unit' => 'bag',
            'item_type_id' => $bulkType->id,
            'current_warehouse_id' => $warehouse1->id,
        ]);

        // Ensure bulk item is not returned by list_assets
        WarehouseServer::tool(ListAssetsTool::class, [])
            ->assertOk()
            ->assertSee('Excavator Cat 320')
            ->assertSee('Bulldozer Komatsu D65')
            ->assertDontSee('Portland Cement 50kg');

        // Search filter
        WarehouseServer::tool(ListAssetsTool::class, ['search' => 'Excavator'])
            ->assertOk()
            ->assertSee('Excavator Cat 320')
            ->assertDontSee('Bulldozer Komatsu D65');

        // Status filter
        WarehouseServer::tool(ListAssetsTool::class, ['status' => 'Damaged'])
            ->assertOk()
            ->assertSee('Bulldozer Komatsu D65')
            ->assertDontSee('Excavator Cat 320');

        // Warehouse filter
        WarehouseServer::tool(ListAssetsTool::class, ['warehouse_id' => $warehouse1->id])
            ->assertOk()
            ->assertSee('Excavator Cat 320')
            ->assertDontSee('Bulldozer Komatsu D65');

        // Second asset type
        $vehicleType = ItemType::create([
            'name' => 'Site Vehicles',
            'base_behavior' => 'ASSET',
        ]);

        $vehicleItem = Item::create([
            'name' => 'Pickup Truck Ford Ranger',
            'specification' => 'Plate ABC-123',
            'unit' => 'unit',
            'item_type_id' => $vehicleType->id,
            'current_warehouse_id' => $warehouse1->id,
            'status' => 'Operational',
        ]);

        // Filter by item_type name
        WarehouseServer::tool(ListAssetsTool::class, ['item_type' => 'Site Vehicles'])
            ->assertOk()
            ->assertSee('Pickup Truck Ford Ranger')
            ->assertDontSee('Excavator Cat 320')
            ->assertDontSee('Bulldozer Komatsu D65');

        // Filter by non-asset item_type returns nothing
        WarehouseServer::tool(ListAssetsTool::class, ['item_type' => 'Construction Material'])
            ->assertOk()
            ->assertDontSee('Portland Cement 50kg')
            ->assertDontSee('Pickup Truck Ford Ranger');

        // Test that schema exposes only ASSET types in the item_type enum
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/list',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 99,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);

        $listAssetsTool = collect($response->json('result.tools'))->firstWhere('name', 'list_assets');
        $this->assertNotNull($listAssetsTool);

        $itemTypeProperty = $listAssetsTool['inputSchema']['properties']['item_type'] ?? null;
        $this->assertNotNull($itemTypeProperty);
        $this->assertArrayHasKey('enum', $itemTypeProperty);

        // Enum should contain 'Asset', 'Heavy Machinery', 'Site Vehicles'
        $this->assertContains('Heavy Machinery', $itemTypeProperty['enum']);
        $this->assertContains('Site Vehicles', $itemTypeProperty['enum']);
        // Enum should NOT contain 'Construction Material' or 'Bulk'
        $this->assertNotContains('Construction Material', $itemTypeProperty['enum']);
        $this->assertNotContains('Bulk', $itemTypeProperty['enum']);
    }

    public function test_list_ledgers_tool_requires_warehouse_and_filters(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $otherWarehouse = Warehouse::create([
            'name' => 'Other Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $itemType = ItemType::create([
            'name' => 'Bulk General',
            'base_behavior' => 'BULK',
        ]);

        $item1 = Item::create([
            'name' => 'Steel Rebar 10mm',
            'unit' => 'pcs',
            'item_type_id' => $itemType->id,
        ]);

        $item2 = Item::create([
            'name' => 'Steel Rebar 16mm',
            'unit' => 'pcs',
            'item_type_id' => $itemType->id,
        ]);

        $ledger1 = Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item1->id,
            'quantity' => 100,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-05-01',
            'created_by' => $user1->id,
        ]);

        $ledger2 = Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item2->id,
            'quantity' => 50,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-05-15',
            'created_by' => $user2->id,
        ]);

        $ledgerOther = Ledger::create([
            'warehouse_id' => $otherWarehouse->id,
            'item_id' => $item1->id,
            'quantity' => 20,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-05-01',
            'created_by' => $user1->id,
        ]);

        // Filter by warehouse_id and item_id
        WarehouseServer::tool(ListLedgersTool::class, [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item1->id,
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertDontSee('Steel Rebar 16mm');

        // Filter by date range
        WarehouseServer::tool(ListLedgersTool::class, [
            'warehouse_id' => $warehouse->id,
            'entry_date_from' => '2026-05-10',
            'entry_date_to' => '2026-05-20',
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 16mm')
            ->assertDontSee('Steel Rebar 10mm');

        // Filter by created_by
        WarehouseServer::tool(ListLedgersTool::class, [
            'warehouse_id' => $warehouse->id,
            'created_by' => $user1->id,
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertDontSee('Steel Rebar 16mm');

        // Missing required warehouse_id should produce error
        WarehouseServer::tool(ListLedgersTool::class, [])
            ->assertHasErrors(['The warehouse id field is required.']);
    }

    public function test_list_items_tool_only_returns_bulk_items_and_supports_name_wildcard_search(): void
    {
        $assetType = ItemType::create([
            'name' => 'Tools & Equipment',
            'base_behavior' => 'ASSET',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Raw Materials',
            'base_behavior' => 'BULK',
        ]);

        $bulkItem1 = Item::create([
            'name' => 'Portland Cement Type 1',
            'specification' => '40kg bag',
            'unit' => 'bag',
            'item_type_id' => $bulkType->id,
        ]);

        $bulkItem2 = Item::create([
            'name' => 'Sand Coarse Grade',
            'specification' => 'River sand',
            'unit' => 'cu.m',
            'item_type_id' => $bulkType->id,
        ]);

        $assetItem = Item::create([
            'name' => 'Concrete Mixer 1-Bagger',
            'specification' => 'Diesel Engine',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
        ]);

        // Default query without filters should return all Bulk items and exclude Assets
        WarehouseServer::tool(ListItemsTool::class, [])
            ->assertOk()
            ->assertSee('Portland Cement Type 1')
            ->assertSee('Sand Coarse Grade')
            ->assertDontSee('Concrete Mixer 1-Bagger');

        // Filter by name wildcard match
        WarehouseServer::tool(ListItemsTool::class, ['name' => 'Cement'])
            ->assertOk()
            ->assertSee('Portland Cement Type 1')
            ->assertDontSee('Sand Coarse Grade')
            ->assertDontSee('Concrete Mixer 1-Bagger');

        // Filter by name with no match
        WarehouseServer::tool(ListItemsTool::class, ['name' => 'Gravel'])
            ->assertOk()
            ->assertDontSee('Portland Cement Type 1')
            ->assertDontSee('Sand Coarse Grade');

        // Additional bulk type for testing item_type filter
        $plumbingType = ItemType::create([
            'name' => 'Plumbing Supplies',
            'base_behavior' => 'BULK',
        ]);

        $pipeItem = Item::create([
            'name' => 'PVC Pipe 2-inch',
            'specification' => 'Blue 3m',
            'unit' => 'length',
            'item_type_id' => $plumbingType->id,
        ]);

        // Filter by item_type
        WarehouseServer::tool(ListItemsTool::class, ['item_type' => 'Plumbing Supplies'])
            ->assertOk()
            ->assertSee('PVC Pipe 2-inch')
            ->assertDontSee('Portland Cement Type 1')
            ->assertDontSee('Sand Coarse Grade');

        // Filter by non-bulk item_type returns nothing
        WarehouseServer::tool(ListItemsTool::class, ['item_type' => 'Tools & Equipment'])
            ->assertOk()
            ->assertDontSee('Concrete Mixer 1-Bagger')
            ->assertDontSee('PVC Pipe 2-inch');

        // Test that schema exposes only BULK types in the item_type enum
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/list',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 100,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);

        $listItemsTool = collect($response->json('result.tools'))->firstWhere('name', 'list_items');
        $this->assertNotNull($listItemsTool);

        $itemTypeProperty = $listItemsTool['inputSchema']['properties']['item_type'] ?? null;
        $this->assertNotNull($itemTypeProperty);
        $this->assertArrayHasKey('enum', $itemTypeProperty);

        // Enum should contain 'Bulk', 'Raw Materials', 'Plumbing Supplies'
        $this->assertContains('Raw Materials', $itemTypeProperty['enum']);
        $this->assertContains('Plumbing Supplies', $itemTypeProperty['enum']);
        // Enum should NOT contain 'Tools & Equipment' or 'Asset'
        $this->assertNotContains('Tools & Equipment', $itemTypeProperty['enum']);
        $this->assertNotContains('Asset', $itemTypeProperty['enum']);
    }

    public function test_list_item_types_tool_returns_all_types_and_filters_by_base_behavior(): void
    {
        $assetType = ItemType::create([
            'name' => 'Heavy Vehicles',
            'base_behavior' => 'ASSET',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Electrical Supplies',
            'base_behavior' => 'BULK',
        ]);

        // Default query without filters should return all item types
        WarehouseServer::tool(ListItemTypesTool::class, [])
            ->assertOk()
            ->assertSee('Heavy Vehicles')
            ->assertSee('Electrical Supplies');

        // Filter by base_behavior = 'BULK'
        WarehouseServer::tool(ListItemTypesTool::class, ['base_behavior' => 'BULK'])
            ->assertOk()
            ->assertSee('Electrical Supplies')
            ->assertDontSee('Heavy Vehicles');

        // Filter by base_behavior = 'ASSET'
        WarehouseServer::tool(ListItemTypesTool::class, ['base_behavior' => 'ASSET'])
            ->assertOk()
            ->assertSee('Heavy Vehicles')
            ->assertDontSee('Electrical Supplies');

        // Filter by search name
        WarehouseServer::tool(ListItemTypesTool::class, ['search' => 'Electrical'])
            ->assertOk()
            ->assertSee('Electrical Supplies')
            ->assertDontSee('Heavy Vehicles');
    }
}
