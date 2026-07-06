<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoggerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_can_see_assigned_warehouses_in_sidebar()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Logger Warehouse',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        $response = $this->actingAs($logger)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('My Warehouses');
        $response->assertSee($warehouse->name);
    }

    public function test_logger_can_access_warehouse_dashboard()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Logger Warehouse',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        $response = $this->actingAs($logger)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(200);
        $response->assertSee($warehouse->name);
        $response->assertSee('Dashboard');
        $response->assertSee('data-filter="CONSUMABLE"', false);
        $response->assertSee('data-filter="ASSET"', false);
        $response->assertSee('filter-btn');
    }

    public function test_logger_cannot_access_unassigned_warehouse_dashboard()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Secret Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($logger)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(404);
    }

    public function test_logger_can_access_rules_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Logger Warehouse',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        // Access rules page while logged in
        $response = $this->actingAs($logger)->get(route('logger.rules'));
        $response->assertStatus(200);
        $response->assertSee('Movement Rules Guide');
        $response->assertSee('INITIAL_STOCK');
        $response->assertSee('UTILIZE');

        // Assert unauthenticated is redirected
        auth()->logout();
        $response = $this->get(route('logger.rules'));
        $response->assertRedirect(route('login'));
    }

    public function test_responsive_mobile_navigation_elements_are_present()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('navbar-toggler');
        $response->assertSee('id="loggerNavbarContent"', false);
    }

    public function test_logger_warehouse_dashboard_shows_asset_status()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Logger Warehouse',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach($warehouse);

        $asset = Item::create([
            'name' => 'Generator X5',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'status' => 'Out of Order',
        ]);

        // Put 1 Generator in stock so it displays on the dashboard
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $asset->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($logger)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(200);
        $response->assertSee('Generator X5');
        $response->assertSee('Out of Order');
        $response->assertSee('Item Count');
        $response->assertSee('class="fw-bold text-primary mb-0 fs-5">1</p>', false);
    }

    public function test_logger_sidebar_groups_warehouses_by_type_and_shows_search_field()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $wh1 = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Central Depot',
            'status' => 'ACTIVE',
        ]);
        $wh2 = Warehouse::create([
            'type' => 'SITE',
            'name' => 'Site Depot',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach([$wh1->id, $wh2->id]);

        $response = $this->actingAs($logger)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('My Warehouses');
        $response->assertSee('warehouse-sidebar-search');
        $response->assertSee('CENTRAL');
        $response->assertSee('SITE');
        $response->assertSee('Central Depot');
        $response->assertSee('Site Depot');
    }

    public function test_logger_sidebar_accordion_active_and_close_all_elements()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $wh1 = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Central Depot',
            'status' => 'ACTIVE',
        ]);
        $wh2 = Warehouse::create([
            'type' => 'SITE',
            'name' => 'Site Depot',
            'status' => 'ACTIVE',
        ]);
        $logger->warehouses()->attach([$wh1->id, $wh2->id]);

        // When accessing the dashboard of $wh1, it is the active warehouse
        $response = $this->actingAs($logger)->get(route('logger.warehouse.dashboard', $wh1));

        $response->assertStatus(200);
        $response->assertSee('id="btn-close-all-warehouses"', false);
        $response->assertSee('data-has-active="true"', false);
        $response->assertSee('data-has-active="false"', false);
        $response->assertSee('bi-pin-angle-fill', false); // Active badge icon
    }
}
