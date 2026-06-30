<?php

namespace Tests\Feature;

use App\Models\AssetUtilization;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetUtilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_query_asset_utilization_with_audit_fields()
    {
        $creator = User::factory()->create(['role' => 'supervisor']);
        $updater = User::factory()->create(['role' => 'admin']);

        $item = Item::create([
            'name' => 'Excavator X9',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'is_asset_utilized' => false,
        ]);

        $this->assertFalse($item->is_asset_utilized);

        $utilization = AssetUtilization::create([
            'item_id' => $item->id,
            'utilized_by' => 'John Doe',
            'utilized_at' => now(),
            'remarks' => 'First usage',
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        $item->update(['is_asset_utilized' => true]);

        $this->assertTrue($item->fresh()->is_asset_utilized);

        $this->assertCount(1, $item->assetUtilizations);
        $this->assertEquals('John Doe', $item->assetUtilizations->first()->utilized_by);
        $this->assertEquals($creator->id, $item->assetUtilizations->first()->created_by);
        $this->assertEquals($updater->id, $item->assetUtilizations->first()->updated_by);

        $this->assertEquals($item->id, $utilization->item->id);
        $this->assertEquals($creator->id, $utilization->creator->id);
        $this->assertEquals($updater->id, $utilization->updater->id);
    }

    public function test_asset_utilization_returned_at_can_be_updated()
    {
        $item = Item::create([
            'name' => 'Generator G10',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'is_asset_utilized' => true,
        ]);

        $utilization = AssetUtilization::create([
            'item_id' => $item->id,
            'utilized_by' => 'Jane Smith',
            'utilized_at' => now()->subDays(2),
        ]);

        $this->assertNull($utilization->returned_at);

        $utilization->update([
            'returned_at' => now(),
            'remarks' => 'Returned safely',
        ]);

        $item->update(['is_asset_utilized' => false]);

        $this->assertNotNull($utilization->fresh()->returned_at);
        $this->assertFalse($item->fresh()->is_asset_utilized);
    }

    public function test_utilize_action_automatically_tracks_asset_utilization_via_ledger_service()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $item = Item::create([
            'name' => 'Bulldozer B50',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'is_asset_utilized' => false,
        ]);

        // Put asset in warehouse first (so there's stock to log OUT)
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO123',
            'delivery_receipt' => 'DR123',
            'plate_no' => 'XYZ-123',
            'status' => 'APPROVED',
        ]);

        $item->update(['current_warehouse_id' => $warehouse->id]);

        $service = resolve(LedgerService::class);

        $this->actingAs($user);

        $service->createEntry([
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'assigned_to' => 'Bob Builder',
            'entry_date' => '2026-06-30',
            'remarks' => 'Road construction job',
        ]);

        $item = $item->fresh();
        $this->assertTrue($item->is_asset_utilized);
        $this->assertNull($item->current_warehouse_id); // OUT clears location

        $this->assertCount(1, $item->assetUtilizations);
        $utilization = $item->assetUtilizations->first();
        $this->assertEquals('Bob Builder', $utilization->utilized_by);
        $this->assertEquals('Road construction job', $utilization->remarks);
        $this->assertEquals('2026-06-30 00:00:00', $utilization->utilized_at->toDateTimeString());
        $this->assertEquals($user->id, $utilization->created_by);
    }

    public function test_utilize_action_for_asset_requires_assigned_to()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $item = Item::create([
            'name' => 'Bulldozer B50',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'is_asset_utilized' => false,
        ]);

        // Put asset in warehouse
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO123',
            'delivery_receipt' => 'DR123',
            'plate_no' => 'XYZ-123',
            'status' => 'APPROVED',
        ]);

        $item->update(['current_warehouse_id' => $warehouse->id]);

        $service = resolve(LedgerService::class);

        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Assigned To is required when utilizing an asset.');

        $service->createEntry([
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'entry_date' => '2026-06-30',
            'remarks' => 'Road construction job',
        ]);
    }
}
