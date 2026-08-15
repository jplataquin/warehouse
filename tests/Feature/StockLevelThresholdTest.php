<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\StockLevelRegistry;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLevelThresholdTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;
    protected $logger;
    protected $warehouse;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->logger = User::factory()->create(['role' => 'logger']);

        $this->warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Central Warehouse',
            'status' => 'ACTIVE',
        ]);

        $itemType = ItemType::firstOrCreate(
            ['name' => 'Consumable'],
            ['base_behavior' => 'CONSUMABLE']
        );

        $this->item = Item::create([
            'name' => 'Steel Rebar',
            'specification' => '12mm',
            'unit' => 'pcs',
            'item_type_id' => $itemType->id,
            'status' => 'Operational',
            'is_approved' => true,
        ]);
    }

    public function test_supervisor_can_set_stock_threshold()
    {
        $response = $this->actingAs($this->supervisor)
            ->post(route('warehouses.thresholds.store', $this->warehouse), [
                'item_id' => $this->item->id,
                'threshold' => 15.50,
            ]);

        $response->assertRedirect(route('warehouses.show', $this->warehouse));
        $response->assertSessionHas('success', 'Stock threshold saved successfully.');

        $this->assertDatabaseHas('stock_level_registries', [
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'threshold' => 15.50,
        ]);
    }

    public function test_logger_cannot_set_stock_threshold()
    {
        $response = $this->actingAs($this->logger)
            ->post(route('warehouses.thresholds.store', $this->warehouse), [
                'item_id' => $this->item->id,
                'threshold' => 15.50,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('stock_level_registries', [
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
        ]);
    }

    public function test_supervisor_can_delete_stock_threshold()
    {
        $threshold = StockLevelRegistry::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'threshold' => 10.00,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->delete(route('warehouses.thresholds.destroy', [$this->warehouse, $threshold]));

        $response->assertRedirect(route('warehouses.show', $this->warehouse));
        $response->assertSessionHas('success', 'Stock threshold deleted successfully.');

        $this->assertDatabaseMissing('stock_level_registries', [
            'id' => $threshold->id,
        ]);
    }

    public function test_low_stock_toast_shows_on_warehouse_dashboard_when_below_threshold()
    {
        // Set threshold to 20
        StockLevelRegistry::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'threshold' => 20.00,
        ]);

        // Add 10 items in stock (below threshold of 20)
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $this->item->id,
            'quantity' => 10.00,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'APPROVED',
        ]);

        // Connect logger to warehouse to view dashboard
        $this->warehouse->loggers()->attach($this->logger->id);

        $response = $this->actingAs($this->logger)
            ->get(route('logger.warehouse.dashboard', $this->warehouse));

        $response->assertStatus(200);
        $response->assertSee('Low Stock Alert!');
        $response->assertSee('Steel Rebar');
        $response->assertSee('10.00 pcs');
    }

    public function test_no_toast_shows_on_warehouse_dashboard_when_above_threshold()
    {
        // Set threshold to 5
        StockLevelRegistry::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'threshold' => 5.00,
        ]);

        // Add 10 items in stock (above threshold of 5)
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $this->item->id,
            'quantity' => 10.00,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'APPROVED',
        ]);

        $this->warehouse->loggers()->attach($this->logger->id);

        $response = $this->actingAs($this->logger)
            ->get(route('logger.warehouse.dashboard', $this->warehouse));

        $response->assertStatus(200);
        $response->assertDontSee('Low Stock Alert!');
    }

    public function test_low_stock_toast_shows_on_item_history_when_below_threshold()
    {
        // Set threshold to 20
        StockLevelRegistry::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'threshold' => 20.00,
        ]);

        // Add 10 items in stock (below threshold of 20)
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $this->item->id,
            'quantity' => 10.00,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ledgers.item_history', ['warehouse' => $this->warehouse->id, 'item' => $this->item->id]));

        $response->assertStatus(200);
        $response->assertSee('Low Stock Alert!');
        $response->assertSee('Steel Rebar');
        $response->assertSee('10.00 pcs');
    }
}
