<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProjectImportTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_supervisor_can_view_project_import_form()
    {
        $response = $this->actingAs($this->supervisor)
            ->get(route('projects.import.form'));

        $response->assertStatus(200);
        $response->assertSee('Bulk Project Import');
        $response->assertSee('Download Template');
    }

    public function test_supervisor_can_download_project_import_template()
    {
        $response = $this->actingAs($this->supervisor)
            ->get(route('projects.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=project_import_template.xlsx');
    }

    public function test_supervisor_can_preview_project_import()
    {
        // Create a real CSV file for testing
        $content = "name,create_warehouse\nProject Alpha,YES\nProject Beta,NO";
        $file = UploadedFile::fake()->createWithContent('projects.csv', $content);

        $response = $this->actingAs($this->supervisor)
            ->post(route('projects.import.preview'), [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertSee('Project Alpha');
        $response->assertSee('Project Beta');
    }

    public function test_supervisor_can_store_project_import()
    {
        $previewData = [
            [
                'name' => 'New Project X',
                'create_warehouse' => true,
                'row_number' => 2,
                'is_valid' => true,
                'errors' => []
            ]
        ];

        session(['project_import_data' => $previewData]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('projects.import.store'));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', ['name' => 'New Project X']);
        
        $project = Project::where('name', 'New Project X')->first();
        $this->assertDatabaseHas('warehouses', [
            'project_id' => $project->id,
            'name' => 'New Project X - Site Warehouse'
        ]);
    }
}
