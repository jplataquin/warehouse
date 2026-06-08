<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_project_index_search_filters_results()
    {
        Project::create(['name' => 'Alpha Project']);
        Project::create(['name' => 'Beta Project']);

        $response = $this->actingAs($this->supervisor)
            ->get(route('projects.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Alpha Project');
        $response->assertDontSee('Beta Project');
    }

    public function test_project_index_clear_search_shows_all_results()
    {
        Project::create(['name' => 'Alpha Project']);
        Project::create(['name' => 'Beta Project']);

        $response = $this->actingAs($this->supervisor)
            ->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertSee('Alpha Project');
        $response->assertSee('Beta Project');
    }
}
