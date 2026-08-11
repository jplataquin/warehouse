<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoggerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_access_edit_assignments_page_for_logger()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($supervisor)->get(route('users.assignments.edit', $logger));

        $response->assertStatus(200);
        $response->assertSee("Assign Warehouses to {$logger->name}");
    }

    public function test_logger_cannot_access_edit_assignments_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $otherLogger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($logger)->get(route('users.assignments.edit', $otherLogger));

        $response->assertStatus(403); // Unauthorized because logger role cannot manage assignments
    }

    public function test_supervisor_can_sync_warehouses_for_logger()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $warehouse1 = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main Warehouse 1',
            'status' => 'ACTIVE',
        ]);
        $warehouse2 = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main Warehouse 2',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($supervisor)->post(route('users.assignments.update', $logger), [
            'warehouse_ids' => [$warehouse1->id, $warehouse2->id],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue($logger->warehouses->contains($warehouse1->id));
        $this->assertTrue($logger->warehouses->contains($warehouse2->id));
    }

    public function test_cannot_assign_warehouses_to_supervisor()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($admin)->get(route('users.assignments.edit', $supervisor));

        $response->assertStatus(404); // User is not a logger or viewer
    }
}
