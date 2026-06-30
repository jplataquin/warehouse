<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_supervisor_can_view_asset_inventory_page()
    {
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $asset = Item::create([
            'name' => 'Excavator X1',
            'type' => 'ASSET',
            'specification' => 'Large',
            'unit' => 'UNIT',
            'current_warehouse_id' => $warehouse->id,
        ]);

        $consumable = Item::create([
            'name' => 'Cement',
            'type' => 'CONSUMABLE',
            'specification' => '40kg',
            'unit' => 'BAG',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('items.assets'));

        $response->assertStatus(200);
        $response->assertSee('Excavator X1');
        $response->assertSee('Main Warehouse');
        $response->assertDontSee('Cement');
    }

    public function test_asset_inventory_search_filters_results()
    {
        Item::create(['name' => 'Truck A', 'type' => 'ASSET', 'unit' => 'UNIT']);
        Item::create(['name' => 'Drill B', 'type' => 'ASSET', 'unit' => 'UNIT']);

        $response = $this->actingAs($this->supervisor)
            ->get(route('items.assets', ['search' => 'Truck']));

        $response->assertStatus(200);
        $response->assertSee('Truck A');
        $response->assertDontSee('Drill B');
    }

    public function test_logger_can_view_asset_inventory_page_but_cannot_add_item()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        Item::create(['name' => 'Excavator X1', 'type' => 'ASSET', 'unit' => 'UNIT']);

        $response = $this->actingAs($logger)
            ->get(route('items.assets'));

        $response->assertStatus(200);
        $response->assertSee('Excavator X1');
        $response->assertSee('Asset Inventory');
        $response->assertDontSee('Add New Item');
    }

    public function test_logger_can_see_status_dropdown_on_ledgers_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'name' => 'Logger Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        $asset = Item::create(['name' => 'Drill D1', 'type' => 'ASSET', 'unit' => 'UNIT']);

        $response = $this->actingAs($logger)
            ->get(route('ledgers.index', ['warehouse_id' => $warehouse->id, 'item_id' => $asset->id]));

        $response->assertStatus(200);
        $response->assertSee('Drill D1');
        $response->assertSee('Asset Status');
        $response->assertSee('select name="status"', false);
    }

    public function test_logger_can_update_asset_status_via_dropdown()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $asset = Item::create(['name' => 'Drill D1', 'type' => 'ASSET', 'unit' => 'UNIT']);

        $this->assertEquals('Operational', $asset->fresh()->status);

        $response = $this->actingAs($logger)
            ->patch(route('items.update-status', $asset), [
                'status' => 'Out of Order',
            ]);

        $response->assertRedirect();
        $this->assertEquals('Out of Order', $asset->fresh()->status);
    }

    public function test_asset_status_defaults_to_operational_and_can_be_updated_to_out_of_order()
    {
        $asset = Item::create([
            'name' => 'Bulldozer B2',
            'type' => 'ASSET',
            'unit' => 'UNIT',
        ]);

        // Default should be Operational
        $this->assertEquals('Operational', $asset->fresh()->status);

        // View assets page and verify Operational badge is shown
        $response = $this->actingAs($this->supervisor)
            ->get(route('items.assets'));
        $response->assertStatus(200);
        $response->assertSee('Operational');

        // Update status to 'Out of Order'
        $response = $this->actingAs($this->supervisor)
            ->put(route('items.update', $asset), [
                'type' => 'ASSET',
                'name' => 'Bulldozer B2',
                'unit' => 'UNIT',
                'status' => 'Out of Order',
            ]);

        $response->assertRedirect(route('items.index'));
        $this->assertEquals('Out of Order', $asset->fresh()->status);

        // View assets page again and verify Out of Order badge is shown
        $response = $this->actingAs($this->supervisor)
            ->get(route('items.assets'));
        $response->assertStatus(200);
        $response->assertSee('Out of Order');
    }
}
