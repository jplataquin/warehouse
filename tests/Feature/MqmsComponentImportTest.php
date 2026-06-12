<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Warehouse;
use App\Models\Allocation;
use App\Services\MqmsApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class MqmsComponentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'supervisor']);
        $this->project = Project::create([
            'name' => 'MQMS Project',
            'mapped_to_project_id' => 'MQ-123'
        ]);
        $this->warehouse = Warehouse::create([
            'name' => 'Site WH',
            'type' => 'SITE',
            'project_id' => $this->project->id,
            'status' => 'ACTIVE'
        ]);
    }

    public function test_sections_endpoint_returns_json()
    {
        $mockClient = Mockery::mock(MqmsApiClient::class);
        $mockClient->shouldReceive('getSections')
            ->with(['project_id' => 'MQ-123'])
            ->once()
            ->andReturn(['data' => [['id' => 'S1', 'name' => 'Section 1']]]);

        $this->app->instance(MqmsApiClient::class, $mockClient);

        $response = $this->actingAs($this->user)
            ->get(route('warehouses.import-components.sections', $this->warehouse));

        $response->assertStatus(200);
        $response->assertJson([['id' => 'S1', 'name' => 'Section 1']]);
    }

    public function test_preview_endpoint_fetches_components()
    {
        $mockClient = Mockery::mock(MqmsApiClient::class);
        $mockClient->shouldReceive('getComponents')
            ->with(['section_id' => 'S1', 'status' => 'APRV'])
            ->once()
            ->andReturn(['data' => [['id' => 'C1', 'name' => 'Component 1']]]);

        $this->app->instance(MqmsApiClient::class, $mockClient);

        $response = $this->actingAs($this->user)
            ->get(route('warehouses.import-components.preview', [
                'warehouse' => $this->warehouse,
                'section_id' => 'S1'
            ]));

        $response->assertStatus(200);
        $response->assertViewIs('supervisor.warehouses.import-components.preview');
        $response->assertSee('Component 1');
    }

    public function test_store_creates_allocations()
    {
        $response = $this->actingAs($this->user)
            ->post(route('warehouses.import-components.store', $this->warehouse), [
                'selected_components' => [
                    ['id' => 'C1', 'name' => 'New Component']
                ]
            ]);

        $response->assertRedirect(route('warehouses.show', $this->warehouse));
        $this->assertDatabaseHas('allocations', [
            'warehouse_id' => $this->warehouse->id,
            'name' => 'New Component',
            'mapped_to_component_id' => 'C1'
        ]);
    }

    public function test_store_prevents_duplicates()
    {
        Allocation::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Existing',
            'mapped_to_component_id' => 'C1'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('warehouses.import-components.store', $this->warehouse), [
                'selected_components' => [
                    ['id' => 'C1', 'name' => 'Existing']
                ]
            ]);

        // Should not create a new one with same mapped ID
        $this->assertEquals(1, Allocation::where('warehouse_id', $this->warehouse->id)
            ->where('mapped_to_component_id', 'C1')
            ->count());
    }

    public function test_store_warns_when_no_components_selected_or_all_unchecked()
    {
        $response = $this->actingAs($this->user)
            ->post(route('warehouses.import-components.store', $this->warehouse), [
                'selected_components' => [
                    ['name' => 'Component A'], // No ID, represents unchecked checkbox
                    ['name' => 'Component B'],  // No ID, represents unchecked checkbox
                ]
            ]);

        $response->assertRedirect(route('warehouses.show', $this->warehouse));
        $response->assertSessionHas('warning', 'No components were selected for import.');
        $this->assertEquals(1, Allocation::count());
        $this->assertDatabaseMissing('allocations', [
            'name' => 'Component A'
        ]);
        $this->assertDatabaseMissing('allocations', [
            'name' => 'Component B'
        ]);
    }
}
