<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\LedgerDiscrepancy;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerDiscrepancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_actively_logs_discrepancy_when_entry_causes_negative_balance()
    {
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $item = Item::create(['name' => 'Safety Gloves', 'type' => 'BULK', 'unit' => 'pairs']);

        // Direct OUT without prior stock
        $ledger = Ledger::create([
            'entry_date' => '2026-01-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item->id,
            'quantity' => 5,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        $this->assertDatabaseHas('ledger_discrepancies', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'ledger_id' => $ledger->id,
            'status' => 'DISCREPANCY',
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function test_passive_check_logs_discrepancy_when_page_is_viewed()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['name' => 'Depot Warehouse', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $item = Item::create(['name' => 'Wrench', 'type' => 'ASSET', 'unit' => 'pcs']);

        $ledger = Ledger::withoutEvents(function () use ($warehouse, $item) {
            return Ledger::create([
                'entry_date' => '2026-01-01',
                'type' => 'OUT',
                'action' => 'ALLOCATE',
                'item_id' => $item->id,
                'quantity' => 1,
                'warehouse_id' => $warehouse->id,
                'status' => 'APPROVED',
            ]);
        });

        // Ensure no discrepancy was created by observer
        $this->assertDatabaseMissing('ledger_discrepancies', [
            'ledger_id' => $ledger->id,
        ]);

        // Access the item history page (Passive trigger)
        $response = $this->actingAs($admin)->get(route('ledgers.item_history', [
            'warehouse' => $warehouse->id,
            'item' => $item->id,
        ]));

        $response->assertStatus(200);

        // Discrepancy should now be logged passively
        $this->assertDatabaseHas('ledger_discrepancies', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'ledger_id' => $ledger->id,
            'status' => 'DISCREPANCY',
        ]);
    }

    public function test_discrepancy_is_not_duplicated_if_unresolved_log_already_exists()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['name' => 'Storage Hub', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $item = Item::create(['name' => 'Cables', 'type' => 'BULK', 'unit' => 'meters']);

        $ledger = Ledger::create([
            'entry_date' => '2026-01-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        $this->assertEquals(1, LedgerDiscrepancy::where('ledger_id', $ledger->id)->count());

        // Repeated page views
        $this->actingAs($admin)->get(route('ledgers.item_history', [
            'warehouse' => $warehouse->id,
            'item' => $item->id,
        ]));

        $this->actingAs($admin)->get(route('ledgers.index', [
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
        ]));

        // Still exactly 1 record with status DISCREPANCY
        $this->assertEquals(1, LedgerDiscrepancy::where('ledger_id', $ledger->id)->where('status', 'DISCREPANCY')->count());
    }

    public function test_admin_and_supervisor_can_view_discrepancies_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $warehouse = Warehouse::create(['name' => 'Central Hub', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $item = Item::create(['name' => 'Valves', 'type' => 'BULK', 'unit' => 'pcs']);

        $ledger = Ledger::create([
            'entry_date' => '2026-01-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item->id,
            'quantity' => 3,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        // Admin can access
        $adminResponse = $this->actingAs($admin)->get(route('discrepancies.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Discrepancy Logs');
        $adminResponse->assertSee('Valves');
        $adminResponse->assertSee("Entry #{$ledger->id}");

        // Supervisor can access
        $supervisorResponse = $this->actingAs($supervisor)->get(route('discrepancies.index'));
        $supervisorResponse->assertStatus(200);
        $supervisorResponse->assertSee('Discrepancy Logs');
        $supervisorResponse->assertSee('Valves');

        // Logger cannot access
        $loggerResponse = $this->actingAs($logger)->get(route('discrepancies.index'));
        $loggerResponse->assertStatus(403);
    }

    public function test_admin_and_supervisor_can_resolve_discrepancy()
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'role' => 'admin']);
        $supervisor = User::factory()->create(['name' => 'Supervisor Jane', 'role' => 'supervisor']);

        $warehouse = Warehouse::create(['name' => 'Site Yard', 'type' => 'SITE', 'status' => 'ACTIVE']);
        $item = Item::create(['name' => 'Cones', 'type' => 'BULK', 'unit' => 'pcs']);

        $ledger = Ledger::create([
            'entry_date' => '2026-01-01',
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item->id,
            'quantity' => 20,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
        ]);

        $discrepancy = LedgerDiscrepancy::where('ledger_id', $ledger->id)->first();
        $this->assertNotNull($discrepancy);
        $this->assertEquals('DISCREPANCY', $discrepancy->status);

        // Supervisor resolves it
        $resolveResponse = $this->actingAs($supervisor)->patch(route('discrepancies.resolve', $discrepancy));
        $resolveResponse->assertRedirect();
        $resolveResponse->assertSessionHas('success');

        $discrepancy->refresh();
        $this->assertEquals('RESOLVED', $discrepancy->status);
        $this->assertEquals($supervisor->id, $discrepancy->resolved_by);
        $this->assertNotNull($discrepancy->resolved_at);

        // Check index view reflects resolved status and resolver name
        $indexResponse = $this->actingAs($admin)->get(route('discrepancies.index', ['status' => 'RESOLVED']));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Supervisor Jane');
        $indexResponse->assertSee('RESOLVED');
    }
}
