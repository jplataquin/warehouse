<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemType;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnreturnedAssetsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_unreturned_assets_report(): void
    {
        $response = $this->get(route('reports.unreturned-assets'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_unreturned_assets_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.unreturned-assets'));
        $response->assertStatus(200);
        $response->assertSee('Unreturned Assets Report');
    }

    public function test_unreturned_assets_report_accurately_lists_unreturned_assets_and_calculates_days(): void
    {
        $user = User::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $assetType = ItemType::create([
            'name' => 'Heavy Equipment',
            'base_behavior' => 'ASSET',
        ]);

        $bulkType = ItemType::create([
            'name' => 'Raw Materials',
            'base_behavior' => 'BULK',
        ]);

        // Asset 1: OUT UTILIZE 10 days ago (Unreturned)
        $asset1 = Item::create([
            'name' => 'Excavator CAT 320',
            'specification' => 'Serial 111',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => null,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $asset1->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'assigned_to' => 'John Operator',
            'plate_no' => 'XYZ-123',
            'entry_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // Asset 2: OUT MAINTENANCE 5 days ago (Unreturned)
        $asset2 = Item::create([
            'name' => 'Bulldozer D65',
            'specification' => 'Serial 222',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => null,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $asset2->id,
            'type' => 'OUT',
            'action' => 'MAINTENANCE',
            'assigned_to' => 'Repair Shop',
            'plate_no' => 'ABC-789',
            'entry_date' => Carbon::today()->subDays(5)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // Asset 3: OUT UTILIZE then RETURNED IN (Returned - should NOT appear)
        $asset3 = Item::create([
            'name' => 'Forklift 3-Ton',
            'specification' => 'Serial 333',
            'unit' => 'unit',
            'item_type_id' => $assetType->id,
            'current_warehouse_id' => $warehouse->id,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $asset3->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'entry_date' => Carbon::today()->subDays(15)->format('Y-m-d'),
            'quantity' => 1,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $asset3->id,
            'type' => 'IN',
            'action' => 'RETURN_ASSET',
            'entry_date' => Carbon::today()->subDays(2)->format('Y-m-d'),
            'quantity' => 1,
        ]);

        // Bulk Item: should NOT appear in unreturned assets
        $bulkItem = Item::create([
            'name' => 'Portland Cement',
            'unit' => 'bag',
            'item_type_id' => $bulkType->id,
        ]);
        Ledger::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $bulkItem->id,
            'type' => 'OUT',
            'action' => 'UTILIZE',
            'entry_date' => Carbon::today()->subDays(20)->format('Y-m-d'),
            'quantity' => 50,
        ]);

        $response = $this->actingAs($user)->get(route('reports.unreturned-assets'));
        $response->assertStatus(200);

        // Assets out should be visible
        $response->assertSee('Excavator CAT 320');
        $response->assertSee('10 days');
        $response->assertSee('Bulldozer D65');
        $response->assertSee('5 days');

        // Returned asset and bulk item should NOT be present
        $response->assertDontSee('Forklift 3-Ton');
        $response->assertDontSee('Portland Cement');

        // Test Printable view
        $printResponse = $this->actingAs($user)->get(route('reports.unreturned-assets.print'));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Excavator CAT 320');
        $printResponse->assertSee('Bulldozer D65');
        $printResponse->assertDontSee('Forklift 3-Ton');
    }

    public function test_reports_index_contains_unreturned_assets_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee(route('reports.unreturned-assets'));
        $response->assertSee('Unreturned Assets');
    }
}
