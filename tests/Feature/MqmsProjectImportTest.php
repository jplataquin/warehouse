<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\MqmsApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MqmsProjectImportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    public function test_can_view_import_preview()
    {
        $this->mock(MqmsApiClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getProjects')
                ->with(['status' => 'ACTV'])
                ->once()
                ->andReturn([
                    'data' => [
                        ['id' => 'MQ-101', 'name' => 'Project Alpha'],
                        ['id' => 'MQ-102', 'name' => 'Project Beta'],
                    ]
                ]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('projects.mqms-import.preview'));

        $response->assertStatus(200);
        $response->assertSee('Project Alpha');
        $response->assertSee('Project Beta');
        $response->assertSee('MQ-101');
    }

    public function test_detects_duplicate_names_in_preview()
    {
        Project::create(['name' => 'Existing Project']);

        $this->mock(MqmsApiClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getProjects')
                ->andReturn([
                    ['id' => 'MQ-101', 'name' => 'Existing Project'],
                    ['id' => 'MQ-102', 'name' => 'New Project'],
                ]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('projects.mqms-import.preview'));

        $response->assertStatus(200);
        $response->assertSee('Project name already exists in database (check deleted projects).');
        $response->assertSee('Ready to Import');
    }

    public function test_can_store_selected_projects()
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.mqms-import.store'), [
                'selected_projects' => [
                    ['id' => 'MQ-101', 'name' => 'Imported Project 1'],
                    ['id' => 'MQ-102', 'name' => 'Imported Project 2'],
                ]
            ]);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success', 'Successfully imported 2 projects.');

        $this->assertDatabaseHas('projects', [
            'name' => 'Imported Project 1',
            'mapped_to_project_id' => 'MQ-101'
        ]);
        $this->assertDatabaseHas('projects', [
            'name' => 'Imported Project 2',
            'mapped_to_project_id' => 'MQ-102'
        ]);
    }

    public function test_can_auto_create_site_warehouse_during_import()
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.mqms-import.store'), [
                'selected_projects' => [
                    [
                        'id' => 'MQ-WH-1', 
                        'name' => 'Project with Warehouse', 
                        'create_warehouse' => '1'
                    ],
                ]
            ]);

        $response->assertRedirect(route('projects.index'));
        
        $project = Project::where('mapped_to_project_id', 'MQ-WH-1')->first();
        $this->assertNotNull($project);

        $this->assertDatabaseHas('warehouses', [
            'project_id' => $project->id,
            'type' => 'SITE',
            'name' => 'Project with Warehouse - Site Warehouse',
            'status' => 'ACTIVE'
        ]);
    }
}
