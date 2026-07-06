<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
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
}
