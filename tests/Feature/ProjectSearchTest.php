<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_supervisor_can_access_projects_index()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)
            ->get(route('projects.index'));

        $response->assertStatus(200);
    }

    public function test_project_index_search_filters_results()
    {
        Project::create(['name' => 'Alpha Project']);
        Project::create(['name' => 'Beta Project']);

        $response = $this->actingAs($this->admin)
            ->get(route('projects.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Alpha Project');
        $response->assertDontSee('Beta Project');
    }

    public function test_project_index_clear_search_shows_all_results()
    {
        Project::create(['name' => 'Alpha Project']);
        Project::create(['name' => 'Beta Project']);

        $response = $this->actingAs($this->admin)
            ->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertSee('Alpha Project');
        $response->assertSee('Beta Project');
    }
}
