<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class WarehouseImportTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_supervisor_can_view_warehouse_import_form()
    {
        $response = $this->actingAs($this->supervisor)
            ->get(route('warehouses.import.form'));

        $response->assertStatus(200);
        $response->assertSee('Bulk Warehouse Import');
    }

    public function test_supervisor_can_download_warehouse_import_template()
    {
        $response = $this->actingAs($this->supervisor)
            ->get(route('warehouses.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=warehouse_import_template.xlsx');
    }

    public function test_supervisor_can_preview_warehouse_import()
    {
        $content = "name,type\nSite Warehouse A,SITE\nCentral Warehouse B,CENTRAL";
        $file = UploadedFile::fake()->createWithContent('warehouses.csv', $content);

        $response = $this->actingAs($this->supervisor)
            ->post(route('warehouses.import.preview'), [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertSee('Site Warehouse A');
        $response->assertSee('Central Warehouse B');
    }

    public function test_supervisor_can_store_warehouse_import()
    {
        $previewData = [
            [
                'name' => 'New Site WH',
                'type' => 'SITE',
                'project_id' => null,
                'status' => 'ACTIVE',
                'row_number' => 2,
                'is_valid' => true,
                'errors' => []
            ],
            [
                'name' => 'New Central WH',
                'type' => 'CENTRAL',
                'project_id' => null,
                'status' => 'ACTIVE',
                'row_number' => 3,
                'is_valid' => true,
                'errors' => []
            ]
        ];

        session(['warehouse_import_data' => $previewData]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('warehouses.import.store'));

        $response->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', ['name' => 'New Site WH', 'status' => 'ACTIVE']);
        $this->assertDatabaseHas('warehouses', ['name' => 'New Central WH', 'status' => 'ACTIVE']);
    }
}
