<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchableDropdownViewsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    public function test_layout_includes_tom_select_assets_and_scripts_stack()
    {
        $response = $this->actingAs($this->admin)->get(route('items.index'));

        $response->assertStatus(200);
        $response->assertSee('tom-select.bootstrap5.min.css', false);
        $response->assertSee('tom-select.complete.min.js', false);
    }

    public function test_reports_project_allocation_summary_has_searchable_project_dropdown()
    {
        $project = Project::create([
            'name' => 'Project Alpha',
            'code' => 'PRJ-A',
        ]);

        $response = $this->actingAs($this->supervisor)->get(route('reports.project-allocation-summary'));

        $response->assertStatus(200);
        $response->assertSee('select name="project_id"', false);
        $response->assertSee('Project Alpha', false);
    }

    public function test_warehouse_create_and_edit_have_searchable_dropdowns()
    {
        $parentWh = Warehouse::create([
            'name' => 'Parent Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        // Create view
        $response = $this->actingAs($this->supervisor)->get(route('warehouses.create'));
        $response->assertStatus(200);
        $response->assertSee('select name="type"', false);
        $response->assertSee('select name="project_id"', false);
        $response->assertSee('select name="parent_id"', false);
        $response->assertSee('select name="status"', false);

        // Edit view
        $response = $this->actingAs($this->supervisor)->get(route('warehouses.edit', $parentWh));
        $response->assertStatus(200);
        $response->assertSee('select name="type"', false);
        $response->assertSee('select name="project_id"', false);
        $response->assertSee('select name="parent_id"', false);
        $response->assertSee('select name="status"', false);
    }

    public function test_ledger_create_views_have_searchable_template_selects()
    {
        $wh = Warehouse::create([
            'name' => 'Central WH',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->admin)->get(route('ledgers.create', ['warehouse_id' => $wh->id]));
        $response->assertStatus(200);
        $response->assertSee('data-custom-tomselect="true"', false);
        $response->assertSee('dest-warehouse-select', false);
        $response->assertSee('allocation-select', false);
    }

    public function test_admin_ledger_edit_view_has_searchable_selects()
    {
        $wh = Warehouse::create([
            'name' => 'Central WH',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $item = Item::create([
            'name' => 'Drill D1',
            'type' => 'CONSUMABLE',
            'unit' => 'pcs',
        ]);
        $ledger = Ledger::create([
            'warehouse_id' => $wh->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'type' => 'IN',
            'action' => 'DELIVERY',
            'user_id' => $this->admin->id,
            'entry_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('ledgers.edit', $ledger));
        $response->assertStatus(200);
        $response->assertSee('select name="item_id"', false);
        $response->assertSee('select name="warehouse_id"', false);
        $response->assertSee('select name="type"', false);
        $response->assertSee('select name="action"', false);
        $response->assertSee('data-custom-tomselect="true"', false);
    }

    public function test_user_management_views_have_searchable_role_dropdown()
    {
        $response = $this->actingAs($this->admin)->get(route('users.create'));
        $response->assertStatus(200);
        $response->assertSee('select name="role"', false);
        $response->assertSee('value="admin"', false);
        $response->assertSee('value="supervisor"', false);
        $response->assertSee('value="logger"', false);
        $response->assertSee('value="viewer"', false);

        $response = $this->actingAs($this->admin)->get(route('users.edit', $this->supervisor));
        $response->assertStatus(200);
        $response->assertSee('select name="role"', false);
    }

    public function test_item_types_views_have_searchable_base_behavior_dropdown()
    {
        $response = $this->actingAs($this->admin)->get(route('item-types.create'));
        $response->assertStatus(200);
        $response->assertSee('select name="base_behavior"', false);

        $itemType = ItemType::first();
        if ($itemType) {
            $response = $this->actingAs($this->admin)->get(route('item-types.edit', $itemType));
            $response->assertStatus(200);
            $response->assertSee('select name="base_behavior"', false);
        }
    }
}
