<?php

namespace Tests\Feature;

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
            'status' => 'ACTIVE'
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
            'status' => 'ACTIVE'
        ]);
        $logger->warehouses()->attach($warehouse);

        $response = $this->actingAs($logger)
            ->get(route('logger.warehouse.dashboard', $warehouse));

        $response->assertStatus(200);
        $response->assertSee($warehouse->name . ' Dashboard');
    }

    public function test_logger_cannot_access_unassigned_warehouse_dashboard()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Secret Warehouse',
            'status' => 'ACTIVE'
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
            'status' => 'ACTIVE'
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
}
