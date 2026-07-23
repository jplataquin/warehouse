<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
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
                ],
            ],
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
                ],
            ],
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
                ],
            ],
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
                ],
            ],
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
                ],
            ],
        ]);

        $response->assertSessionHas('success', 'Ledger entries created successfully.');
        $this->assertDatabaseHas('ledgers', [
            'po_number' => 'PO-123',
            'delivery_receipt' => 'DR-456',
            'plate_no' => 'PLAT-123',
            'offical_receipt' => '',
        ]);
    }

    public function test_asset_return_requires_remarks()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Drill', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ],
            ],
        ]);

        $response->assertSessionHas('error', 'Remarks are required for asset returns.');
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
                ],
            ],
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
                ],
            ],
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
                ],
            ],
        ]);

        $response->assertSessionHas('error', 'Asset items can only be processed one at a time (quantity must be 1).');
    }

    public function test_asset_return_not_allowed_for_consumable()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Test remarks',
                ],
            ],
        ]);

        $response->assertSessionHas('error', 'Asset Return action can only be performed on ASSET items.');
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
                ],
            ],
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
                ],
            ],
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
                ],
            ],
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
            'remarks' => 'REMARK_DELIVERY',
        ]);

        Ledger::create([
            'entry_date' => now(), 'type' => 'OUT', 'action' => 'DISPOSE', 'item_id' => $item->id,
            'quantity' => 1.23, 'warehouse_id' => $warehouse->id, 'status' => 'APPROVED',
            'remarks' => 'REMARK_DISPOSE',
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
                ],
            ],
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

    public function test_utilize_action_rules()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // 1. UTILIZE must be OUT type
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'UTILIZE',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Using some cement',
                ],
            ],
        ]);
        $response->assertSessionHas('error', 'UTILIZE action must be of type OUT.');

        // 2. UTILIZE requires remarks
        // First add stock so we don't fail on stock check
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
                    'action' => 'UTILIZE',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => '', // Missing
                ],
            ],
        ]);
        $response->assertSessionHas('error', 'Remarks are required for UTILIZE movements.');

        // 3. Valid UTILIZE action works
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'OUT',
                    'action' => 'UTILIZE',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Utilizing 1 bag for floor repair',
                ],
            ],
        ]);
        $response->assertSessionHas('success', 'Ledger entries created successfully.');
        $this->assertDatabaseHas('ledgers', [
            'item_id' => $item->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'quantity' => 1,
            'remarks' => 'Utilizing 1 bag for floor repair',
        ]);
    }

    public function test_asset_return_requires_last_entry_to_be_utilize_in_current_warehouse()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Bulldozer B20', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Attempting to return without any previous ledger entry at all
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Returning asset',
                ],
            ],
        ]);
        $response->assertSessionHas('error', 'The asset has no record of being logged out for utilization in this warehouse.');

        // Add a DELIVERY IN (valid)
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
            'plate_no' => 'PL-1',
            'status' => 'APPROVED',
        ]);
        $item->update(['current_warehouse_id' => $warehouse->id]);

        // Log it out with LOST (valid OUT action) to clear current_warehouse_id but make the last action in warehouse LOST (not UTILIZE)
        Ledger::create([
            'entry_date' => now(),
            'type' => 'OUT',
            'action' => 'LOST',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Asset lost',
            'status' => 'APPROVED',
        ]);
        $item->update(['current_warehouse_id' => null]);

        // Attempting to return when the last entry in the warehouse is LOST (not OUT UTILIZE)
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Returning asset',
                ],
            ],
        ]);
        $response->assertSessionHas('error', 'The asset has no record of being logged out for utilization in this warehouse.');
    }

    public function test_asset_return_throws_if_already_returned()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Bulldozer B20', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Create a DELIVERY IN
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
            'plate_no' => 'PL-1',
            'status' => 'APPROVED',
        ]);

        // Create OUT UTILIZE linked to a return (i.e. already returned)
        Ledger::create([
            'entry_date' => now(),
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'from_ledger_id' => 999, // Set to dummy linked ID
            'remarks' => 'Utilized',
            'status' => 'APPROVED',
        ]);

        // Attempt ASSET_RETURN
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Returning asset',
                ],
            ],
        ]);
        $response->assertSessionHas('error', 'The asset has already been returned for its last utilization.');
    }

    public function test_asset_return_mutually_links_ledger_records()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'ASSET', 'name' => 'Bulldozer B20', 'unit' => 'Units']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Create a DELIVERY IN
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
            'plate_no' => 'PL-1',
            'status' => 'APPROVED',
        ]);
        $item->update(['current_warehouse_id' => $warehouse->id]);

        // Create OUT UTILIZE (unlinked)
        $utilize = Ledger::create([
            'entry_date' => now(),
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'assigned_to' => 'Bob',
            'remarks' => 'Utilized',
            'status' => 'APPROVED',
        ]);
        $item->update(['current_warehouse_id' => null, 'is_asset_utilized' => true]);

        // Post ASSET_RETURN
        $response = $this->actingAs($user)->post('/ledgers', [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'ASSET_RETURN',
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'warehouse_id' => $warehouse->id,
                    'remarks' => 'Returning asset after utilization',
                ],
            ],
        ]);

        $response->assertSessionHas('success', 'Ledger entries created successfully.');

        $returnLedger = Ledger::where('item_id', $item->id)
            ->where('action', 'ASSET_RETURN')
            ->first();

        $this->assertNotNull($returnLedger);
        $this->assertEquals($utilize->id, $returnLedger->from_ledger_id);
        $this->assertEquals($returnLedger->id, $utilize->fresh()->from_ledger_id);
    }

    public function test_admin_can_access_edit_ledger_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);
        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial',
        ]);

        $response = $this->actingAs($admin)->get("/ledgers/{$ledger->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.ledgers.edit');
    }

    public function test_non_admin_cannot_access_edit_ledger_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);
        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial',
        ]);

        $response = $this->actingAs($logger)->get("/ledgers/{$ledger->id}/edit");

        $response->assertStatus(403);
    }

    public function test_admin_can_update_ledger_entry_and_records_updated_by()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);
        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial',
        ]);

        $response = $this->actingAs($admin)->put("/ledgers/{$ledger->id}", [
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 20, // Change from 10 to 20
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Updated Remarks',
        ]);

        $response->assertRedirect(route('ledgers.show', $ledger));
        $this->assertEquals(20, $ledger->fresh()->quantity);
        $this->assertEquals('Updated Remarks', $ledger->fresh()->remarks);
        $this->assertEquals($admin->id, $ledger->fresh()->updated_by);
    }

    public function test_admin_update_validates_business_rules()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);
        
        // Initial stock of 10
        $ledger1 = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial',
        ]);

        // Out movement of 8 (available stock = 2)
        $ledger2 = Ledger::create([
            'entry_date' => now(),
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 8,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Utilize',
        ]);

        // Admin tries to update the Out movement to 15, which exceeds available stock (stock without entry is 10, requested 15)
        $response = $this->actingAs($admin)->put("/ledgers/{$ledger2->id}", [
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 15,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Utilize more than stock',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(8, $ledger2->fresh()->quantity); // Quantity should remain unchanged
    }

    public function test_logger_can_access_item_history_print_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'South', 'status' => 'ACTIVE']);

        $response = $this->actingAs($logger)->get(route('ledgers.item_history.print', ['warehouse' => $warehouse->id, 'item' => $item->id]));

        $response->assertStatus(200);
        $response->assertSee('ITEM LEDGER');
        $response->assertSee('Cement');
        $response->assertSee('Generated on');
    }
}
