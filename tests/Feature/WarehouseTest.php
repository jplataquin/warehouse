<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use \Illuminate\Foundation\Testing\WithoutMiddleware, RefreshDatabase;

    public function test_supervisor_can_view_warehouse_details()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.show', $warehouse));

        $response->assertStatus(200);
        $response->assertSee($warehouse->name);
        $response->assertSee('Warehouse Information');
        $response->assertSee('Assigned Loggers');
        $response->assertSee('Actions');
    }

    public function test_warehouse_index_shows_clickable_rows_without_loggers_column()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.index'));

        $response->assertStatus(200);
        $response->assertSee('clickable-row');
        $response->assertDontSee('Loggers</th>');
        $response->assertSee(route('warehouses.show', $warehouse));
    }

    public function test_supervisor_can_assign_logger_from_show_page()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test Warehouse',
            'status' => 'ACTIVE',
        ]);
        $logger = User::factory()->create(['role' => 'logger', 'name' => 'John Doe']);

        $response = $this->actingAs($supervisor)
            ->post(route('warehouses.loggers.assign', $warehouse), [
                'logger_id' => $logger->id,
            ]);

        $response->assertRedirect(route('warehouses.show', $warehouse));
        $this->assertTrue($warehouse->loggers->contains($logger));
    }

    public function test_warehouse_index_search_filters_results()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main Warehouse',
            'status' => 'ACTIVE',
        ]);
        Warehouse::create([
            'type' => 'SITE',
            'name' => 'Sub Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.index', ['search' => 'Main']));

        $response->assertStatus(200);
        $response->assertSee('Main Warehouse');
        $response->assertDontSee('Sub Warehouse');
    }

    public function test_warehouse_index_search_filters_results_by_project_name()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = Project::create(['name' => 'Project Alpha']);
        Warehouse::create([
            'project_id' => $project->id,
            'type' => 'CENTRAL',
            'name' => 'Main Warehouse',
            'status' => 'ACTIVE',
        ]);
        Warehouse::create([
            'type' => 'SITE',
            'name' => 'Sub Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Main Warehouse');
        $response->assertDontSee('Sub Warehouse');
    }

    public function test_supervisor_can_create_warehouse_manually_and_status_is_saved_in_all_caps()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        // Test with title-case 'Active'
        $response1 = $this->actingAs($supervisor)
            ->post(route('warehouses.store'), [
                'name' => 'Manual Site WH 1',
                'type' => 'SITE',
                'status' => 'Active',
            ]);

        $response1->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Manual Site WH 1',
            'status' => 'ACTIVE',
        ]);

        // Test with all-caps 'ACTIVE'
        $response2 = $this->actingAs($supervisor)
            ->post(route('warehouses.store'), [
                'name' => 'Manual Site WH 2',
                'type' => 'SITE',
                'status' => 'ACTIVE',
            ]);

        $response2->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Manual Site WH 2',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_supervisor_can_update_warehouse_manually_and_status_is_saved_in_all_caps()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'name' => 'Original WH',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        // Test updating to title-case 'Deactivated'
        $response1 = $this->actingAs($supervisor)
            ->put(route('warehouses.update', $warehouse), [
                'name' => 'Updated WH 1',
                'type' => 'CENTRAL',
                'status' => 'Deactivated',
            ]);

        $response1->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Updated WH 1',
            'status' => 'DEACTIVATED',
        ]);

        // Test updating to all-caps 'DEACTIVATED'
        $response2 = $this->actingAs($supervisor)
            ->put(route('warehouses.update', $warehouse), [
                'name' => 'Updated WH 2',
                'type' => 'CENTRAL',
                'status' => 'DEACTIVATED',
            ]);

        $response2->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Updated WH 2',
            'status' => 'DEACTIVATED',
        ]);
    }

    public function test_supervisor_can_create_new_warehouse_types_without_project()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        // 1. Create EQUIPMENT/VEHICLE
        $response1 = $this->actingAs($supervisor)
            ->post(route('warehouses.store'), [
                'name' => 'Fleet Garage',
                'type' => 'EQUIPMENT/VEHICLE',
                'project_id' => null,
                'status' => 'ACTIVE',
            ]);

        $response1->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Fleet Garage',
            'type' => 'EQUIPMENT/VEHICLE',
            'project_id' => null,
            'status' => 'ACTIVE',
        ]);

        // 2. Create OFFICE/FACILITY
        $response2 = $this->actingAs($supervisor)
            ->post(route('warehouses.store'), [
                'name' => 'HQ Depot',
                'type' => 'OFFICE/FACILITY',
                'project_id' => null,
                'status' => 'ACTIVE',
            ]);

        $response2->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'HQ Depot',
            'type' => 'OFFICE/FACILITY',
            'project_id' => null,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_sub_warehouses_are_filtered_out_of_warehouses_index()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $parentWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Parent Warehouse',
            'status' => 'ACTIVE',
        ]);
        $subWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Sub Warehouse Under Parent',
            'status' => 'ACTIVE',
            'parent_id' => $parentWarehouse->id,
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.index'));

        $response->assertStatus(200);
        $response->assertSee('Parent Warehouse');
        $response->assertDontSee('Sub Warehouse Under Parent');
    }

    public function test_sub_warehouses_section_shown_only_on_central_warehouse_detail_page()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        // 1. Central warehouse with a sub-warehouse
        $centralWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Central Warehouse Parent',
            'status' => 'ACTIVE',
        ]);
        $subWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Central Sub Warehouse',
            'status' => 'ACTIVE',
            'parent_id' => $centralWarehouse->id,
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('warehouses.show', $centralWarehouse));

        $response->assertStatus(200);
        $response->assertSee('Sub-Warehouses');
        $response->assertSee('Central Sub Warehouse');

        // 2. Site warehouse with a sub-warehouse (to test the condition)
        $siteWarehouse = Warehouse::create([
            'type' => 'SITE',
            'name' => 'Site Warehouse Parent',
            'status' => 'ACTIVE',
        ]);
        $siteSubWarehouse = Warehouse::create([
            'type' => 'SITE',
            'name' => 'Site Sub Warehouse',
            'status' => 'ACTIVE',
            'parent_id' => $siteWarehouse->id,
        ]);

        $response2 = $this->actingAs($supervisor)
            ->get(route('warehouses.show', $siteWarehouse));

        $response2->assertStatus(200);
        $response2->assertDontSee('Sub-Warehouses');
        $response2->assertDontSee('Site Sub Warehouse');
    }

    public function test_cannot_delete_warehouse_with_non_zero_quantity_items()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test WH with Items',
            'status' => 'ACTIVE',
        ]);
        $item = Item::create([
            'type' => 'BULK',
            'name' => 'Steel Rebar',
            'unit' => 'Pcs',
        ]);

        // Add 10 items
        Ledger::create([
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'remarks' => 'Initial count',
        ]);

        $response = $this->actingAs($supervisor)
            ->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete warehouse because it still contains items with non-zero quantity.');
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_can_delete_warehouse_with_zero_quantity_items()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test WH with Zero Items',
            'status' => 'ACTIVE',
        ]);
        $item = Item::create([
            'type' => 'BULK',
            'name' => 'Steel Rebar',
            'unit' => 'Pcs',
        ]);

        // Add 10 items
        Ledger::create([
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'remarks' => 'Initial count',
        ]);

        // Remove 10 items (balanced to 0)
        Ledger::create([
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($supervisor)
            ->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect(route('warehouses.index'));
        $response->assertSessionHas('success', 'Warehouse deleted successfully.');
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    public function test_cannot_delete_parent_warehouse_if_sub_warehouse_has_non_zero_quantity_items()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $parentWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Parent WH',
            'status' => 'ACTIVE',
        ]);
        $subWarehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Sub WH',
            'status' => 'ACTIVE',
            'parent_id' => $parentWarehouse->id,
        ]);
        $item = Item::create([
            'type' => 'BULK',
            'name' => 'Sand',
            'unit' => 'm3',
        ]);

        // Log non-zero quantity in the sub-warehouse
        Ledger::create([
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 5,
            'warehouse_id' => $subWarehouse->id,
            'status' => 'APPROVED',
            'remarks' => 'Initial count',
        ]);

        $response = $this->actingAs($supervisor)
            ->delete(route('warehouses.destroy', $parentWarehouse));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete warehouse because it still contains items with non-zero quantity.');
        $this->assertDatabaseHas('warehouses', ['id' => $parentWarehouse->id]);
        $this->assertDatabaseHas('warehouses', ['id' => $subWarehouse->id]);
    }

    public function test_logger_can_view_explore_other_warehouses_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $myWarehouse = Warehouse::create([
            'name' => 'Assigned Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($myWarehouse);

        $otherWarehouse = Warehouse::create([
            'name' => 'Unassigned Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($logger)->get(route('logger.warehouses.explore'));

        $response->assertStatus(200);
        $response->assertSee('Explore Other Warehouses');
        $response->assertSee('Unassigned Warehouse');
        $response->assertDontSee('My Warehouses');
        $response->assertViewHas('otherWarehouses', function ($warehouses) use ($otherWarehouse, $myWarehouse) {
            return $warehouses->contains($otherWarehouse) && ! $warehouses->contains($myWarehouse);
        });
    }

    public function test_logger_can_view_unassigned_warehouse_dashboard_in_view_only_mode()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $otherWarehouse = Warehouse::create([
            'name' => 'Unassigned Warehouse 2',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($logger)->get(route('logger.warehouse.dashboard', $otherWarehouse));

        $response->assertStatus(200);
        $response->assertSee('View Only');
        $response->assertSee('OTHER WAREHOUSE: Unassigned Warehouse 2');
        $response->assertSee('Exploring other warehouse');
        $response->assertDontSee('My Warehouses');
        $response->assertDontSee('New Entry');
        $response->assertDontSee('Add New Item');
    }

    public function test_logger_cannot_create_entry_in_unassigned_warehouse()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $assignedWarehouse = Warehouse::create([
            'name' => 'Assigned Warehouse 3',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($assignedWarehouse);

        $otherWarehouse = Warehouse::create([
            'name' => 'Unassigned Warehouse 3',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        // Attempting to access create page
        $createResponse = $this->actingAs($logger)->get(route('ledgers.create', ['warehouse_id' => $otherWarehouse->id]));
        $createResponse->assertStatus(403);

        // Attempting to store an entry
        $item = Item::create(['name' => 'Cement', 'type' => 'BULK', 'unit' => 'bag']);
        $storeResponse = $this->actingAs($logger)->post(route('ledgers.store'), [
            'entries' => [
                [
                    'entry_date' => now()->toDateString(),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'warehouse_id' => $otherWarehouse->id,
                ],
            ],
        ]);

        $storeResponse->assertStatus(403);
    }

    public function test_logger_can_print_warehouse_inventory_showing_only_items_with_stock()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'name' => 'Inventory Print WH',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        // Bulk item with positive stock
        $itemWithStock = Item::create(['name' => 'Steel Rebar', 'type' => 'BULK', 'unit' => 'pcs']);
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $itemWithStock->id,
            'quantity' => 50,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        // Asset item with positive stock
        $assetWithStock = Item::create(['name' => 'Excavator', 'type' => 'ASSET', 'unit' => 'unit']);
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $assetWithStock->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        // Item with zero stock (IN then OUT)
        $itemZeroStock = Item::create(['name' => 'Gravel', 'type' => 'BULK', 'unit' => 'm3']);
        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $itemZeroStock->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);
        Ledger::create([
            'entry_date' => now(),
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $itemZeroStock->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        // Item without any ledger movements
        $itemNoStock = Item::create(['name' => 'Paint', 'type' => 'BULK', 'unit' => 'can']);

        $response = $this->actingAs($logger)->get(route('logger.warehouse.inventory.print', $warehouse));

        $response->assertStatus(200);
        $response->assertSee('WAREHOUSE INVENTORY REPORT');
        $response->assertSee('Inventory Print WH');
        $response->assertSee('Bulk Items');
        $response->assertSee('Asset Items');
        $response->assertSee('Steel Rebar');
        $response->assertSee('50.00 pcs');
        $response->assertSee('Excavator');
        $response->assertSee('1.00 unit');
        $response->assertDontSee('<th>Type</th>');
        $response->assertDontSee('Gravel');
        $response->assertDontSee('Paint');
    }
}
