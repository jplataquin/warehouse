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
}
