<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_see_assigned_warehouses_in_sidebar()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('My Warehouses');
        $response->assertSee($warehouse->name);
        $response->assertSee('Viewer Console');
    }

    public function test_viewer_can_access_warehouse_dashboard()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(200);
        $response->assertSee($warehouse->name);
        $response->assertSee('Dashboard');
        $response->assertSee('data-filter="CONSUMABLE"', false);
        $response->assertSee('data-filter="ASSET"', false);
        $response->assertSee('filter-btn');
    }

    public function test_viewer_cannot_access_unassigned_warehouse_dashboard()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Secret Warehouse',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(404);
    }

    public function test_viewer_cannot_see_quick_actions_on_dashboard()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(200);
        $response->assertDontSee('Quick Actions');
        $response->assertDontSee('New Entry');
        $response->assertDontSee('Add New Item');
        $response->assertDontSee('Create Sub-Wh');
    }

    public function test_viewer_cannot_access_create_item_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->get(route('logger.items.create'));

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_store_item_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->post(route('logger.items.store'), [
                'name' => 'Unauthorized Item',
            ]);

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_create_sub_warehouse_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)
            ->get(route('logger.sub-warehouses.create', $warehouse));

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_store_sub_warehouse_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)
            ->post(route('logger.sub-warehouses.store', $warehouse), [
                'name' => 'Sub Warehouse Draft',
            ]);

        $response->assertStatus(403);
    }

    public function test_viewer_can_access_rules_page()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)->get(route('logger.rules'));
        $response->assertStatus(200);
        $response->assertSee('Movement Rules Guide');
    }

    public function test_viewer_can_access_ledger_index_with_warehouse()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Viewer Warehouse',
            'status' => 'ACTIVE',
        ]);
        $viewer->warehouses()->attach($warehouse);

        $response = $this->actingAs($viewer)->get(route('ledgers.index', ['warehouse_id' => $warehouse->id]));
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_access_create_ledger_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('ledgers.create'));
        $response->assertStatus(403);
    }

    public function test_viewer_cannot_access_store_ledger_route()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->post(route('ledgers.store'), [
            'entries' => [
                [
                    'entry_date' => now()->format('Y-m-d'),
                    'type' => 'IN',
                    'action' => 'DELIVERY',
                    'item_id' => 1,
                    'quantity' => 10,
                    'warehouse_id' => 1,
                ]
            ]
        ]);
        $response->assertStatus(403);
    }
}
