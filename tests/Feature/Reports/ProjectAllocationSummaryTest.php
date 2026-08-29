<?php

namespace Tests\Feature\Reports;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAllocationSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);
        $this->item = Item::create([
            'name' => 'Test Item',
            'type' => 'CONSUMABLE',
            'unit' => 'Pcs',
        ]);
    }

    /**
     * Test that guests are redirected to login.
     */
    public function test_guests_cannot_access_reports_pages(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));

        $this->get(route('reports.project-allocation-summary'))
            ->assertRedirect(route('login'));
    }

    /**
     * Test that authenticated users can access reports index.
     */
    public function test_authenticated_users_can_access_reports_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Project Allocation Summary');
    }

    /**
     * Test that the project allocation summary page loads without a selected project.
     */
    public function test_project_allocation_summary_loads_without_selected_project(): void
    {
        $project = Project::create(['name' => 'Project A']);

        $response = $this->actingAs($this->user)
            ->get(route('reports.project-allocation-summary'));

        $response->assertStatus(200);
        $response->assertSee('Please select a project');
        $response->assertSee('Project A');
    }

    /**
     * Test correct aggregation and filtering logic of the report.
     */
    public function test_project_allocation_summary_aggregates_and_filters_correctly(): void
    {
        // 1. Create Projects
        $project1 = Project::create(['name' => 'Project One']);
        $project2 = Project::create(['name' => 'Project Two']);

        // 2. Create Allocations
        $allocation1 = Allocation::create(['name' => 'Allocation Alpha', 'warehouse_id' => $this->warehouse->id]);
        $allocation2 = Allocation::create(['name' => 'Allocation Beta', 'warehouse_id' => $this->warehouse->id]);

        // Create Second Item
        $item2 = Item::create([
            'name' => 'Second Item',
            'type' => 'CONSUMABLE',
            'unit' => 'Bags',
        ]);

        // 3. Create Ledger entries for Project One (within and outside range)
        // Ledger 1: Project One, Allocation Alpha, Qty 10.00, Date: 2026-08-01, Action: ALLOCATE (In range)
        Ledger::create([
            'entry_date' => '2026-08-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 10.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation1->id,
        ]);

        // Ledger 2: Project One, Allocation Alpha, Qty 15.50, Date: 2026-08-05, Action: ALLOCATE (In range)
        Ledger::create([
            'entry_date' => '2026-08-05',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 15.50,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation1->id,
        ]);

        // Ledger 7: Project One, Allocation Alpha, Qty 8.00, Date: 2026-08-07, Action: ALLOCATE (In range, Second Item)
        Ledger::create([
            'entry_date' => '2026-08-07',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item2->id,
            'quantity' => 8.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation1->id,
        ]);

        // Ledger 3: Project One, Allocation Beta, Qty 20.00, Date: 2026-08-10, Action: ALLOCATE (In range)
        Ledger::create([
            'entry_date' => '2026-08-10',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 20.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation2->id,
        ]);

        // Ledger 4: Project One, Allocation Beta, Qty 5.00, Date: 2026-08-20, Action: ALLOCATE (OUTSIDE To-Date Range)
        Ledger::create([
            'entry_date' => '2026-08-20',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 5.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation2->id,
        ]);

        // Ledger 5: Project One, Allocation Alpha, Qty 30.00, Date: 2026-08-03, Action: TRANSFER (Different action, shouldn't sum)
        Ledger::create([
            'entry_date' => '2026-08-03',
            'type' => 'OUT',
            'action' => 'TRANSFER',
            'item_id' => $this->item->id,
            'quantity' => 30.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project1->id,
            'allocation_id' => $allocation1->id,
        ]);

        // Ledger 6: Project Two, Allocation Alpha, Qty 50.00, Date: 2026-08-03, Action: ALLOCATE (Different project, shouldn't sum)
        Ledger::create([
            'entry_date' => '2026-08-03',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 50.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project2->id,
            'allocation_id' => $allocation1->id,
        ]);

        // 4. Test filtering with Project 1 and Date Range: 2026-08-01 to 2026-08-15
        $response = $this->actingAs($this->user)
            ->get(route('reports.project-allocation-summary', [
                'project_id' => $project1->id,
                'from_date' => '2026-08-01',
                'to_date' => '2026-08-15',
            ]));

        $response->assertStatus(200);

        // Alpha total should be (10.00 + 15.50) = 25.50 (for Test Item) + 8.00 (for Second Item)
        $response->assertSee('Allocation Alpha');
        $response->assertSee('Test Item');
        $response->assertSee('25.50');
        $response->assertSee('Second Item');
        $response->assertSee('8.00');

        // Beta total should be 20.00 (Ledger 4 with 5.00 is out of date range)
        $response->assertSee('Allocation Beta');
        $response->assertSee('20.00');

        // Check that Project Two's 50.00 is NOT present
        $response->assertDontSee('50.00');

        // 5. Test filtering with Project 2
        $response2 = $this->actingAs($this->user)
            ->get(route('reports.project-allocation-summary', [
                'project_id' => $project2->id,
            ]));

        $response2->assertStatus(200);
        $response2->assertSee('Allocation Alpha');
        $response2->assertSee('50.00');
    }

    /**
     * Test that guests cannot access reports details page.
     */
    public function test_guests_cannot_access_reports_details_page(): void
    {
        $this->get(route('reports.project-allocation-summary.details'))
            ->assertRedirect(route('login'));
    }

    /**
     * Test that reports details page loads and displays contributing ledger entries.
     */
    public function test_reports_details_loads_correctly_with_data_and_filters(): void
    {
        $project = Project::create(['name' => 'Project Gamma']);
        $allocation = Allocation::create(['name' => 'Allocation Gamma', 'warehouse_id' => $this->warehouse->id]);
        
        // Ledger entry 1 contributing
        $ledger1 = Ledger::create([
            'entry_date' => '2026-08-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 12.50,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project->id,
            'allocation_id' => $allocation->id,
            'po_number' => 'PO-12345',
            'remarks' => 'Test contributing entry 1',
        ]);

        // Ledger entry 2 contributing
        $ledger2 = Ledger::create([
            'entry_date' => '2026-08-10',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 7.50,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project->id,
            'allocation_id' => $allocation->id,
            'delivery_receipt' => 'DR-67890',
            'remarks' => 'Test contributing entry 2',
        ]);

        // Ledger entry 3 - outside date range
        $ledger3 = Ledger::create([
            'entry_date' => '2026-08-20',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $this->item->id,
            'quantity' => 50.00,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $project->id,
            'allocation_id' => $allocation->id,
        ]);

        // Query the details page with date range filter (excluding entry 3)
        $response = $this->actingAs($this->user)
            ->get(route('reports.project-allocation-summary.details', [
                'project_id' => $project->id,
                'allocation_id' => $allocation->id,
                'item_id' => $this->item->id,
                'from_date' => '2026-08-01',
                'to_date' => '2026-08-15',
            ]));

        $response->assertStatus(200);
        
        // Should show correct Context
        $response->assertSee('Project Gamma');
        $response->assertSee('Allocation Gamma');
        $response->assertSee('Test Item');
        
        // Should display total sum of filtered entries: 12.50 + 7.50 = 20.00
        $response->assertSee('Total Sum: 20.00');

        // Should display contributing entries
        $response->assertSee('PO-12345');
        $response->assertSee('DR-67890');
        $response->assertSee('Test contributing entry 1');
        $response->assertSee('Test contributing entry 2');

        // Should NOT show the quantity from the out-of-range entry (50.00)
        $response->assertDontSee('Total Sum: 70.00');
    }
}

