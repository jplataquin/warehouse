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
        $warehouse = Warehouse::create(['name' => 'Main', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);

        $item = Item::create([
            'name' => 'Excavator X9',
            'type' => 'ASSET',
            'unit' => 'UNIT',
            'is_asset_utilized' => false,
        ]);

        $ledger = Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO123',
            'delivery_receipt' => 'DR123',
            'plate_no' => 'PL-1',
            'status' => 'APPROVED',
        ]);

        $utilization = AssetUtilization::create([
            'item_id' => $item->id,
            'ledger_id' => $ledger->id,
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        $this->assertEquals($item->id, $utilization->item->id);
        $this->assertEquals($ledger->id, $utilization->ledger->id);
        $this->assertEquals($creator->id, $utilization->creator->id);
        $this->assertEquals($updater->id, $utilization->updater->id);
    }

    public function test_utilize_and_asset_return_actions_automatically_link_to_asset_utilizations()
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

        // 1. DELIVERY IN (valid)
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

        // 2. OUT UTILIZE (Automatically tracks OUT in asset_utilizations)
        $utilizeLedger = $service->createEntry([
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

        // Assert asset_utilizations contains the UTILIZE record link
        $this->assertCount(1, $item->assetUtilizations);
        $this->assertEquals($utilizeLedger->id, $item->assetUtilizations->first()->ledger_id);

        // 3. IN ASSET_RETURN (Automatically tracks IN in asset_utilizations)
        $returnLedger = $service->createEntry([
            'type' => 'IN',
            'action' => 'ASSET_RETURN',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'entry_date' => '2026-06-30',
            'remarks' => 'Returning bulldozer after construction',
        ]);

        $item = $item->fresh();
        $this->assertFalse($item->is_asset_utilized);

        // Assert asset_utilizations contains both UTILIZE and ASSET_RETURN records linked
        $this->assertCount(2, $item->assetUtilizations);

        $linkedLedgerIds = $item->assetUtilizations->pluck('ledger_id')->toArray();
        $this->assertContains($utilizeLedger->id, $linkedLedgerIds);
        $this->assertContains($returnLedger->id, $linkedLedgerIds);
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
