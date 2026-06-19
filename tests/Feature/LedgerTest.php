<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Project;
use App\Models\Allocation;
use App\Models\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_delivery_must_be_in_type()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Add some stock first so we don't hit the "no stock" validation
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 100,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-INITIAL',
            'delivery_receipt' => 'DR-INITIAL',
        ]);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Delivery action must be of type IN.');
    }

    public function test_allocate_must_be_consumable()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Drill', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);
        $project = Project::create(['name' => 'Test Project']);
        $allocation = Allocation::create([
            'name' => 'Test Allocation',
            'warehouse_id' => $warehouse->id,
            'project_id' => $project->id,
        ]);

        // Add some stock first
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-INITIAL',
            'delivery_receipt' => 'DR-INITIAL',
        ]);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'ALLOCATE',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'allocation_id' => $allocation->id,
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Allocate action can only be performed on CONSUMABLE items.');
    }

    public function test_consumable_delivery_requires_receipt_fields()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'po_number' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'PO Number is required for item deliveries.');
    }

    public function test_asset_delivery_requires_receipt_fields()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Drill', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'po_number' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'PO Number is required for item deliveries.');
    }

    public function test_official_receipt_is_optional_for_consumable_delivery()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'po_number' => 'PO-123',
                    'delivery_receipt' => 'DR-456',
                    'plate_no' => 'PLAT-123',
                    'offical_receipt' => '',
                ]
            ]
        ]);

        $response->assertSessionHas('success', 'Ledger entries created successfully.');
        $this->assertDatabaseHas('ledgers', [
            'po_number' => 'PO-123',
            'delivery_receipt' => 'DR-456',
            'plate_no' => 'PLAT-123',
            'offical_receipt' => '',
        ]);
    }

    public function test_direct_asset_requires_remarks()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Drill', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DIRECT',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Remarks are required for direct asset/recoverable log-ins.');
    }

    public function test_out_movement_requires_existing_stock()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Attempting OUT without any IN record first
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'DISPOSE',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                ]
            ]
        ]);

        $response->assertSessionHas('error', "Cannot perform OUT movement. Available stock for 'Cement' is 0, but 10 was requested.");
    }

    public function test_dispose_requires_remarks()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Add stock
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
        ]);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'DISPOSE',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Remarks are required for DISPOSE movements.');
    }

    public function test_asset_quantity_must_be_one()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Drill', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 2, // Invalid
                    'warehouse_id' => $warehouse->id,
                    'po_number' => 'PO-1',
                    'delivery_receipt' => 'DR-1',
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Asset items can only be processed one at a time (quantity must be 1).');
    }

    public function test_recoverable_quantity_can_be_more_than_one()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'RECOVERABLE', 'name' => 'Scaffolding', 'unit' => 'Sets']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 50, // More than 1
                    'warehouse_id' => $warehouse->id,
                    'po_number' => 'PO-REC',
                    'delivery_receipt' => 'DR-REC',
                    'plate_no' => 'PLAT-REC',
                ]
            ]
        ]);

        $response->assertSessionHas('success', 'Ledger entries created successfully.');
        $this->assertDatabaseHas('ledgers', [
            'item_id' => $item->id,
            'quantity' => 50,
            'plate_no' => 'PLAT-REC',
        ]);
    }

    public function test_recoverable_delivery_requires_receipt_fields()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'RECOVERABLE', 'name' => 'Scaffolding', 'unit' => 'Sets']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'po_number' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'PO Number is required for item deliveries.');
    }

    public function test_direct_recoverable_requires_remarks()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'RECOVERABLE', 'name' => 'Scaffolding', 'unit' => 'Sets']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DIRECT',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Remarks are required for direct asset/recoverable log-ins.');
    }

    public function test_initial_stock_requires_remarks()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'INITIAL_STOCK',
                    'item_id' => $item->id,
                    'quantity' => 100,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Remarks are required for initial stock entries.');
    }

    public function test_initial_stock_must_be_in_type()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'INITIAL_STOCK',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Reducing initial stock (invalid type)',
                ]
            ]
        ]);

        $response->assertSessionHas('error', 'Initial stock action must be of type IN.');
    }

    public function test_initial_stock_successful()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'INITIAL_STOCK',
                    'item_id' => $item->id,
                    'quantity' => 150,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Starting balance of cement',
                ]
            ]
        ]);

        $response->assertSessionHas('success', 'Ledger entries created successfully.');
        $this->assertDatabaseHas('ledgers', [
            'item_id' => $item->id,
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'quantity' => 150,
            'remarks' => 'Starting balance of cement',
        ]);
    }

    public function test_ledger_can_be_filtered_by_type_and_action()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Create entries of different types and actions
        Ledger::create([
            'entry_date' => now(), 'type' => 'IN', 'action' => 'DELIVERY', 'item_id' => $item->id, 
            'quantity' => 10, 'warehouse_id' => $warehouse->id, 'status' => 'APPROVED',
            'po_number' => 'PO-1', 'delivery_receipt' => 'DR-1',
            'remarks' => 'REMARK_DELIVERY'
        ]);

        Ledger::create([
            'entry_date' => now(), 'type' => 'OUT', 'action' => 'DISPOSE', 'item_id' => $item->id, 
            'quantity' => 1.23, 'warehouse_id' => $warehouse->id, 'status' => 'APPROVED',
            'remarks' => 'REMARK_DISPOSE'
        ]);

        // Filter by Type: IN
        $response = $this->actingAs($user)->get("/ledgers?warehouse_id={$warehouse->id}&type=IN");
        $response->assertStatus(200);
        $response->assertSee('REMARK_DELIVERY');
        $response->assertDontSee('REMARK_DISPOSE');

        // Filter by Action: DISPOSE
        $response = $this->actingAs($user)->get("/ledgers?warehouse_id={$warehouse->id}&action=DISPOSE");
        $response->assertStatus(200);
        $response->assertSee('REMARK_DISPOSE');
        $response->assertDontSee('REMARK_DELIVERY');
    }

    public function test_cannot_enter_future_date()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $futureDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => $futureDate,
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'po_number' => 'PO-1',
                    'delivery_receipt' => 'DR-1',
                ]
            ]
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entries.0.entry_date']);
    }

    public function test_item_history_page_is_accessible()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($admin)->get(route('ledgers.item_history', ['warehouse' => $warehouse->id, 'item' => $item->id]));
        
        $response->assertStatus(200);
        $response->assertSee('Cement');
        $response->assertSee('Movement History');
    }

    public function test_item_history_print_page_is_accessible()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Gravel', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'North', 'status' => 'ACTIVE']);

        $response = $this->actingAs($admin)->get(route('ledgers.item_history.print', ['warehouse' => $warehouse->id, 'item' => $item->id]));
        
        $response->assertStatus(200);
        $response->assertSee('ITEM LEDGER');
        $response->assertSee('Gravel');
    }
}
