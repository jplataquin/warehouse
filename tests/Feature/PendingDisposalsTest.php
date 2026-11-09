<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingDisposalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_pending_disposals()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($logger)->get(route('ledgers.disposals'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_only_pending_disposals()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main Warehouse', 'status' => 'ACTIVE']);
        $item = Item::create(['type' => 'BULK', 'name' => 'Gravel', 'unit' => 'Bags']);

        // 1. Pending disposal
        $pendingDisposal = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 5,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'remarks' => 'Damaged gravel bags',
        ]);

        // 2. Approved disposal
        $approvedDisposal = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'remarks' => 'Already approved disposal',
        ]);

        // 3. Pending non-disposal
        $pendingDelivery = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 20,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($admin)->get(route('ledgers.disposals'));

        $response->assertStatus(200);
        $response->assertSee('Damaged gravel bags');
        $response->assertDontSee('Already approved disposal');
    }

    public function test_admin_can_approve_disposal()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main Warehouse', 'status' => 'ACTIVE']);
        $item = Item::create(['type' => 'BULK', 'name' => 'Sand', 'unit' => 'Bags']);

        $disposal = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'remarks' => 'Contaminated sand',
        ]);

        $response = $this->actingAs($admin)->post(route('ledgers.approve', $disposal));

        $response->assertRedirect();
        $this->assertDatabaseHas('ledgers', [
            'id' => $disposal->id,
            'status' => 'APPROVED',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_disposal_and_soft_delete_record()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main Warehouse', 'status' => 'ACTIVE']);
        $item = Item::create(['type' => 'BULK', 'name' => 'Bricks', 'unit' => 'Pcs']);

        $disposal = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 15,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'remarks' => 'Chipped bricks',
        ]);

        $response = $this->actingAs($admin)->delete(route('ledgers.destroy', $disposal), [
            'delete_reason' => 'These bricks can still be reused.',
            'redirect_to' => route('ledgers.disposals'),
        ]);

        $response->assertRedirect(route('ledgers.disposals'));
        $this->assertSoftDeleted('ledgers', [
            'id' => $disposal->id,
            'delete_reason' => 'These bricks can still be reused.',
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_pending_disposal_for_asset_item()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main Warehouse', 'status' => 'ACTIVE']);
        $item = Item::create([
            'type' => 'ASSET',
            'name' => 'Excavator',
            'unit' => 'Units',
            'current_warehouse_id' => null, // Disposing an asset sets current_warehouse_id to null
        ]);

        $disposal = Ledger::create([
            'entry_date' => now()->format('Y-m-d'),
            'type' => 'OUT',
            'action' => 'DISPOSE',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'remarks' => 'Engine broken',
        ]);

        $response = $this->actingAs($admin)->delete(route('ledgers.destroy', $disposal), [
            'delete_reason' => 'Engine can be repaired under warranty.',
            'redirect_to' => route('ledgers.disposals'),
        ]);

        $response->assertRedirect(route('ledgers.disposals'));
        $this->assertSoftDeleted('ledgers', [
            'id' => $disposal->id,
            'delete_reason' => 'Engine can be repaired under warranty.',
            'deleted_by' => $admin->id,
        ]);

        // Asset item's warehouse should be restored
        $item->refresh();
        $this->assertEquals($warehouse->id, $item->current_warehouse_id);
    }
}
