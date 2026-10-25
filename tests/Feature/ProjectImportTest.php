<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProjectImportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_supervisor_cannot_access_project_import_form()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $response = $this->actingAs($supervisor)
            ->get(route('projects.import.form'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_project_import_form()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('projects.import.form'));

        $response->assertStatus(200);
        $response->assertSee('Bulk Project Import');
        $response->assertSee('Download Template');
    }

    public function test_admin_can_download_project_import_template()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('projects.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=project_import_template.xlsx');
    }

    public function test_admin_can_preview_project_import()
    {
        // Create a real CSV file for testing
        $content = "name,create_warehouse\nProject Alpha,YES\nProject Beta,NO";
        $file = UploadedFile::fake()->createWithContent('projects.csv', $content);

        $response = $this->actingAs($this->admin)
            ->post(route('projects.import.preview'), [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertSee('Project Alpha');
        $response->assertSee('Project Beta');
    }

    public function test_admin_can_store_project_import()
    {
        $previewData = [
            [
                'name' => 'New Project X',
                'create_warehouse' => true,
                'row_number' => 2,
                'is_valid' => true,
                'errors' => [],
            ],
        ];

        session(['project_import_data' => $previewData]);

        $response = $this->actingAs($this->admin)
            ->post(route('projects.import.store'));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', ['name' => 'New Project X']);

        $project = Project::where('name', 'New Project X')->first();
        $this->assertDatabaseHas('warehouses', [
            'project_id' => $project->id,
            'name' => 'New Project X - Site Warehouse',
        ]);
    }
}
