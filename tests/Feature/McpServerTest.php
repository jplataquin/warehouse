<?php

namespace Tests\Feature;

use App\Mcp\Servers\WarehouseServer;
use App\Mcp\Tools\ListAssetsTool;
use App\Mcp\Tools\ListDiscrepancyTool;
use App\Mcp\Tools\ListItemsTool;
use App\Mcp\Tools\ListItemTypesTool;
use App\Mcp\Tools\ListLedgersTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListStockLevelsTool;
use App\Mcp\Tools\ListUnreturnedAssetsTool;
use App\Mcp\Tools\ListWarehousesTool;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Ledger;
use App\Models\LedgerDiscrepancy;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
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
            'list_stock_levels',
            'list_unreturned_assets',
            'list_discrepancy',
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
            ListStockLevelsTool::class,
            ListUnreturnedAssetsTool::class,
            ListDiscrepancyTool::class,
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

    public function test_list_stock_levels_tool_aggregates_stock_and_filters(): void
    {
        $warehouse1 = Warehouse::create([
            'name' => 'Main Depot',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'Secondary Yard',
            'type' => 'SECONDARY',
            'status' => 'ACTIVE',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Raw Materials',
            'base_behavior' => 'BULK',
        ]);

        $assetType = ItemType::create([
            'name' => 'Heavy Equipment',
            'base_behavior' => 'ASSET',
        ]);

        $item1 = Item::create([
            'name' => 'Steel Rebar 10mm',
            'specification' => 'Grade 40',
            'unit' => 'pcs',
            'item_type_id' => $bulkType->id,
        ]);

        $item2 = Item::create([
            'name' => 'Cement Portland',
            'specification' => 'Type 1 40kg',
            'unit' => 'bag',
            'item_type_id' => $bulkType->id,
        ]);

        $item3 = Item::create([
            'name' => 'Generator 5kVA',
            'specification' => 'Diesel Silent',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
        ]);

        // Warehouse 1 ledger entries for item 1: IN 100, OUT 30 => Net 70.00
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item1->id,
            'quantity' => 100,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-10-01',
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item1->id,
            'quantity' => 30,
            'type' => 'OUT',
            'action' => 'DISPATCH',
            'entry_date' => '2026-10-15',
        ]);

        // Warehouse 1 ledger entries for item 2: IN 50, OUT 10 => Net 40.00
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item2->id,
            'quantity' => 50,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-10-20',
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item2->id,
            'quantity' => 10,
            'type' => 'OUT',
            'action' => 'DISPATCH',
            'entry_date' => '2026-11-01',
        ]);

        // Warehouse 1 ledger entries for item 3: IN 5 => Net 5.00
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item3->id,
            'quantity' => 5,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-10-10',
        ]);

        // Warehouse 2 ledger entry (different warehouse, should NOT affect warehouse 1)
        Ledger::create([
            'warehouse_id' => $warehouse2->id,
            'item_id' => $item1->id,
            'quantity' => 200,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'entry_date' => '2026-10-05',
        ]);

        // 1. Missing warehouse_id should fail validation
        WarehouseServer::tool(ListStockLevelsTool::class, [])
            ->assertHasErrors(['The warehouse id field is required.']);

        // 2. Query warehouse 1 with no optional filters (should return all 3 items with correct aggregated stock and last_entry_date)
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertSee('70.00')
            ->assertSee('2026-10-15')
            ->assertSee('Raw Materials')
            ->assertSee('BULK')
            ->assertSee('Cement Portland')
            ->assertSee('40.00')
            ->assertSee('2026-11-01')
            ->assertSee('Generator 5kVA')
            ->assertSee('5.00')
            ->assertSee('2026-10-10')
            ->assertSee('Heavy Equipment')
            ->assertSee('ASSET');

        // 3. Verify tool call and exact JSON structure via JSON-RPC endpoint
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $jsonRpcResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_stock_levels',
                'arguments' => ['warehouse_id' => $warehouse1->id],
            ],
        ]);

        $jsonRpcResponse->assertStatus(200);
        $content = json_decode($jsonRpcResponse->json('result.content.0.text'), true);
        $this->assertCount(3, $content);

        $item1Data = collect($content)->firstWhere('item_id', $item1->id);
        $this->assertNotNull($item1Data);
        $this->assertEquals('Steel Rebar 10mm', $item1Data['name']);
        $this->assertEquals('Grade 40', $item1Data['specification']);
        $this->assertEquals('pcs', $item1Data['unit']);
        $this->assertEquals('Raw Materials', $item1Data['item_type']);
        $this->assertEquals('BULK', $item1Data['base_behavior']);
        $this->assertEquals('70.00', $item1Data['current_stock']);
        $this->assertEquals('2026-10-15', $item1Data['last_entry_date']);

        $item2Data = collect($content)->firstWhere('item_id', $item2->id);
        $this->assertNotNull($item2Data);
        $this->assertEquals('Cement Portland', $item2Data['name']);
        $this->assertEquals('40.00', $item2Data['current_stock']);
        $this->assertEquals('2026-11-01', $item2Data['last_entry_date']);

        $item3Data = collect($content)->firstWhere('item_id', $item3->id);
        $this->assertNotNull($item3Data);
        $this->assertEquals('Heavy Equipment', $item3Data['item_type']);
        $this->assertEquals('ASSET', $item3Data['base_behavior']);
        $this->assertEquals('5.00', $item3Data['current_stock']);
        $this->assertEquals('2026-10-10', $item3Data['last_entry_date']);

        // 4. Filter by item_id
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'item_id' => $item2->id,
        ])
            ->assertOk()
            ->assertSee('Cement Portland')
            ->assertDontSee('Steel Rebar 10mm')
            ->assertDontSee('Generator 5kVA');

        // 5. Wildcard filter by search
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'search' => 'Steel',
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertDontSee('Cement Portland')
            ->assertDontSee('Generator 5kVA');

        // 6. Filter by as_of_date (before OUT 30 on 2026-10-15, item1 had 100.00; item2 had 0 entries before 2026-10-10)
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'as_of_date' => '2026-10-10',
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertSee('100.00')
            ->assertSee('2026-10-01')
            ->assertSee('Generator 5kVA')
            ->assertSee('5.00')
            ->assertDontSee('Cement Portland');

        // 7. Filter by item_type
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'item_type' => 'Heavy Equipment',
        ])
            ->assertOk()
            ->assertSee('Generator 5kVA')
            ->assertDontSee('Steel Rebar 10mm')
            ->assertDontSee('Cement Portland');

        // 8. Filter by base_behavior
        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'base_behavior' => 'BULK',
        ])
            ->assertOk()
            ->assertSee('Steel Rebar 10mm')
            ->assertSee('Cement Portland')
            ->assertDontSee('Generator 5kVA');

        WarehouseServer::tool(ListStockLevelsTool::class, [
            'warehouse_id' => $warehouse1->id,
            'base_behavior' => 'ASSET',
        ])
            ->assertOk()
            ->assertSee('Generator 5kVA')
            ->assertDontSee('Steel Rebar 10mm')
            ->assertDontSee('Cement Portland');
    }

    public function test_list_unreturned_assets_tool_lists_assets_and_filters(): void
    {
        $warehouse1 = Warehouse::create([
            'name' => 'Main Hub',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $warehouse2 = Warehouse::create([
            'name' => 'Field Station',
            'type' => 'SECONDARY',
            'status' => 'ACTIVE',
        ]);

        $assetType = ItemType::create([
            'name' => 'Site Tools',
            'base_behavior' => 'ASSET',
        ]);

        $heavyType = ItemType::create([
            'name' => 'Heavy Equipment',
            'base_behavior' => 'ASSET',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Hardware',
            'base_behavior' => 'BULK',
        ]);

        // Asset 1: OUT UTILIZE from warehouse1 12 days ago
        $asset1 = Item::create([
            'name' => 'Demolition Hammer 15kg',
            'specification' => 'Makita HM1307CB',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => null,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $asset1->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'assigned_to' => 'Jack Hammer',
            'plate_no' => 'DH-01',
            'remarks' => 'Demolition phase 1',
            'entry_date' => Carbon::today()->subDays(12)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // Asset 2: OUT MAINTENANCE from warehouse2 6 days ago
        $asset2 = Item::create([
            'name' => 'Wheel Loader 950M',
            'specification' => 'CAT Diesel',
            'unit' => 'unit',
            'item_type_id' => $heavyType->id,
            'current_warehouse_id' => null,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse2->id,
            'item_id' => $asset2->id,
            'type' => 'OUT',
            'action' => 'MAINTENANCE',
            'assigned_to' => 'CAT Service Center',
            'plate_no' => 'CAT-950',
            'remarks' => 'Hydraulic maintenance',
            'entry_date' => Carbon::today()->subDays(6)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // Asset 3: OUT UTILIZE then ASSET_RETURN IN (Returned, should NOT appear)
        $asset3 = Item::create([
            'name' => 'Survey Theodolite',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => $warehouse1->id,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $asset3->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'entry_date' => Carbon::today()->subDays(20)->format('Y-m-d'),
            'quantity' => 1,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse1->id,
            'item_id' => $asset3->id,
            'type' => 'IN',
            'action' => 'ASSET_RETURN',
            'entry_date' => Carbon::today()->subDays(2)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // 1. Default query returns unreturned assets and includes days_out
        WarehouseServer::tool(ListUnreturnedAssetsTool::class, [])
            ->assertOk()
            ->assertSee('Demolition Hammer 15kg')
            ->assertSee('Makita HM1307CB')
            ->assertSee('UTILIZE')
            ->assertSee('Jack Hammer')
            ->assertSee('12')
            ->assertSee('Wheel Loader 950M')
            ->assertSee('MAINTENANCE')
            ->assertSee('6')
            ->assertDontSee('Survey Theodolite');

        // 2. Filter by warehouse_id
        WarehouseServer::tool(ListUnreturnedAssetsTool::class, [
            'warehouse_id' => $warehouse1->id,
        ])
            ->assertOk()
            ->assertSee('Demolition Hammer 15kg')
            ->assertDontSee('Wheel Loader 950M');

        // 3. Filter by item_type
        WarehouseServer::tool(ListUnreturnedAssetsTool::class, [
            'item_type' => 'Heavy Equipment',
        ])
            ->assertOk()
            ->assertSee('Wheel Loader 950M')
            ->assertDontSee('Demolition Hammer 15kg');

        // 4. Filter by search
        WarehouseServer::tool(ListUnreturnedAssetsTool::class, [
            'search' => 'Makita',
        ])
            ->assertOk()
            ->assertSee('Demolition Hammer 15kg')
            ->assertDontSee('Wheel Loader 950M');

        // 5. Filter by min_days_out
        WarehouseServer::tool(ListUnreturnedAssetsTool::class, [
            'min_days_out' => 10,
        ])
            ->assertOk()
            ->assertSee('Demolition Hammer 15kg')
            ->assertDontSee('Wheel Loader 950M');

        // 6. JSON-RPC test over HTTP
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 999,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_unreturned_assets',
                'arguments' => ['min_days_out' => 5],
            ],
        ]);

        $response->assertStatus(200);
        $content = json_decode($response->json('result.content.0.text'), true);
        $this->assertCount(2, $content);

        $first = $content[0]; // sorted by days_out desc
        $this->assertEquals($asset1->id, $first['item_id']);
        $this->assertEquals(12, $first['days_out']);
        $this->assertEquals('UTILIZE', $first['action']);
        $this->assertEquals('Main Hub', $first['origin_warehouse']);

        $second = $content[1];
        $this->assertEquals($asset2->id, $second['item_id']);
        $this->assertEquals(6, $second['days_out']);
        $this->assertEquals('MAINTENANCE', $second['action']);
        $this->assertEquals('Field Station', $second['origin_warehouse']);
    }

    public function test_list_discrepancy_tool_via_jsonrpc(): void
    {
        $user = User::factory()->create();
        $resolver = User::factory()->create(['name' => 'Supervisor Mark']);
        $token = $user->createToken('test-token')->plainTextToken;

        $warehouseA = Warehouse::create(['name' => 'Warehouse Alpha', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $warehouseB = Warehouse::create(['name' => 'Warehouse Beta', 'type' => 'SITE', 'status' => 'ACTIVE']);
        $item1 = Item::create(['name' => 'Steel Pipes', 'type' => 'BULK', 'unit' => 'pcs']);
        $item2 = Item::create(['name' => 'Safety Harness', 'type' => 'ASSET', 'unit' => 'pcs']);

        $ledger1 = Ledger::withoutEvents(function () use ($warehouseA, $item1) {
            return Ledger::create([
                'entry_date' => '2026-02-01',
                'type' => 'OUT',
                'action' => 'ALLOCATE',
                'item_id' => $item1->id,
                'quantity' => 5,
                'warehouse_id' => $warehouseA->id,
                'status' => 'APPROVED',
            ]);
        });

        $ledger2 = Ledger::withoutEvents(function () use ($warehouseB, $item2) {
            return Ledger::create([
                'entry_date' => '2026-02-02',
                'type' => 'OUT',
                'action' => 'ALLOCATE',
                'item_id' => $item2->id,
                'quantity' => 2,
                'warehouse_id' => $warehouseB->id,
                'status' => 'APPROVED',
            ]);
        });

        $discrepancy1 = LedgerDiscrepancy::create([
            'warehouse_id' => $warehouseA->id,
            'item_id' => $item1->id,
            'ledger_id' => $ledger1->id,
            'status' => 'DISCREPANCY',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $discrepancy2 = LedgerDiscrepancy::create([
            'warehouse_id' => $warehouseB->id,
            'item_id' => $item2->id,
            'ledger_id' => $ledger2->id,
            'status' => 'RESOLVED',
            'resolved_by' => $resolver->id,
            'resolved_at' => '2026-02-03 12:00:00',
            'created_at' => '2026-02-02 11:00:00',
        ]);

        // 1. Call without arguments -> returns both
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 101,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_discrepancy',
                'arguments' => [],
            ],
        ]);

        $response->assertStatus(200);
        $content = json_decode($response->json('result.content.0.text'), true);
        $this->assertCount(2, $content);

        // 2. Filter by status: DISCREPANCY
        $responseDiscrepancyOnly = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 102,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_discrepancy',
                'arguments' => [
                    'status' => 'DISCREPANCY',
                ],
            ],
        ]);

        $responseDiscrepancyOnly->assertStatus(200);
        $contentDiscrepancy = json_decode($responseDiscrepancyOnly->json('result.content.0.text'), true);
        $this->assertCount(1, $contentDiscrepancy);
        $this->assertEquals($discrepancy1->id, $contentDiscrepancy[0]['id']);
        $this->assertEquals('DISCREPANCY', $contentDiscrepancy[0]['status']);
        $this->assertEquals('Steel Pipes', $contentDiscrepancy[0]['item']['name']);

        // 3. Filter by warehouse_id and status: RESOLVED
        $responseResolved = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => 'tools/call',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 103,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_discrepancy',
                'arguments' => [
                    'warehouse_id' => $warehouseB->id,
                    'status' => 'RESOLVED',
                ],
            ],
        ]);

        $responseResolved->assertStatus(200);
        $contentResolved = json_decode($responseResolved->json('result.content.0.text'), true);
        $this->assertCount(1, $contentResolved);
        $this->assertEquals($discrepancy2->id, $contentResolved[0]['id']);
        $this->assertEquals('RESOLVED', $contentResolved[0]['status']);
        $this->assertEquals('Supervisor Mark', $contentResolved[0]['resolver']['name']);
        $this->assertEquals('Safety Harness', $contentResolved[0]['item']['name']);
    }
}
