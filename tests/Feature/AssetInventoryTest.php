<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
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
            'status' => 'ACTIVE'
        ]);

        $asset = Item::create([
            'name' => 'Excavator X1',
            'type' => 'ASSET',
            'specification' => 'Large',
            'unit' => 'UNIT',
            'current_warehouse_id' => $warehouse->id
        ]);

        $consumable = Item::create([
            'name' => 'Cement',
            'type' => 'CONSUMABLE',
            'specification' => '40kg',
            'unit' => 'BAG'
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
}
