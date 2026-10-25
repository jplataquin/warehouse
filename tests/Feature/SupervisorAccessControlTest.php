<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_supervisor_can_access_reports()
    {
        $response = $this->actingAs($this->supervisor)->get(route('reports.index'));
        $response->assertStatus(200);
    }

    public function test_supervisor_can_access_all_ledgers()
    {
        $response = $this->actingAs($this->supervisor)->get(route('ledgers.index'));
        $response->assertStatus(200);
    }

    public function test_supervisor_can_access_assets()
    {
        $response = $this->actingAs($this->supervisor)->get(route('items.assets'));
        $response->assertStatus(200);
    }

    public function test_supervisor_cannot_access_projects()
    {
        $response = $this->actingAs($this->supervisor)->get(route('projects.index'));
        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_access_warehouses()
    {
        $response = $this->actingAs($this->supervisor)->get(route('warehouses.index'));
        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_access_items_index()
    {
        $response = $this->actingAs($this->supervisor)->get(route('items.index'));
        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_access_logger_create_routes()
    {
        $response = $this->actingAs($this->supervisor)->get(route('logger.items.create'));
        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_access_warehouse_dashboard()
    {
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Test WH',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('logger.warehouse.dashboard', $warehouse));
        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_access_logger_assignments()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($this->supervisor)
            ->get(route('users.assignments.edit', $logger));
        $response->assertStatus(403);
    }

    public function test_navbar_only_shows_reports_ledger_and_assets_for_supervisor()
    {
        $response = $this->actingAs($this->supervisor)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Reports');
        $response->assertSee('Ledger');
        $response->assertSee('Assets');
        
        // Restricted navigation links should not be shown
        $response->assertDontSee('href="' . route('projects.index') . '"', false);
        $response->assertDontSee('href="' . route('warehouses.index') . '"', false);
        $response->assertDontSee('href="' . route('items.index') . '"', false);
        $response->assertDontSee('href="' . route('users.index') . '"', false);
        $response->assertDontSee('href="' . route('admin.settings') . '"', false);
    }
}
