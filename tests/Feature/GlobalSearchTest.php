<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_can_search_warehouses_and_ledgers()
    {
        $logger = User::factory()->create(['role' => 'logger']);

        $warehouse = Warehouse::create([
            'name' => 'Target Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $item = Item::create([
            'name' => 'Test Item',
            'type' => 'BULK',
            'unit' => 'PCS',
        ]);

        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-12345',
            'offical_receipt' => 'OR-67890',
            'delivery_receipt' => 'DR-11223',
            'plate_no' => 'PLAT-777',
            'status' => 'PENDING',
        ]);

        // Search for Warehouse
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'Target']));
        $response->assertStatus(200);
        $response->assertSee('Target Warehouse');

        // Search for PO Number
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'PO-12345']));
        $response->assertStatus(200);
        $response->assertSee('PO-12345');

        // Search for Official Receipt
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'OR-67890']));
        $response->assertStatus(200);
        $response->assertSee('OR-67890');

        // Search for Delivery Receipt
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'DR-11223']));
        $response->assertStatus(200);
        $response->assertSee('DR-11223');

        // Search for Plate No
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'PLAT-777']));
        $response->assertStatus(200);
        $response->assertSee('PLAT-777');

        // Search by Item Name
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'Test Item']));
        $response->assertStatus(200);
        $response->assertSee('Test Item');

        // Search by combination of keywords (Plate No and Item Name)
        $response = $this->actingAs($logger)->get(route('global.search', ['query' => 'PLAT-777 Item']));
        $response->assertStatus(200);
        $response->assertSee('Test Item');
        $response->assertSee('PLAT-777');
    }

    public function test_admin_and_supervisor_search_excludes_warehouses()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $warehouse = Warehouse::create([
            'name' => 'Secret Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $item = Item::create([
            'name' => 'Searchable Item',
            'type' => 'BULK',
            'unit' => 'PCS',
        ]);

        Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-ADMIN-1',
            'status' => 'PENDING',
        ]);

        // Admin search for warehouse name
        $response = $this->actingAs($admin)->get(route('global.search', ['query' => 'Secret']));
        $response->assertStatus(200);
        $response->assertViewHas('warehouses', function ($warehouses) {
            return $warehouses->isEmpty();
        });

        // Supervisor search for warehouse name
        $response = $this->actingAs($supervisor)->get(route('global.search', ['query' => 'Secret']));
        $response->assertStatus(200);
        $response->assertViewHas('warehouses', function ($warehouses) {
            return $warehouses->isEmpty();
        });

        // Verify ledger search still works
        $response = $this->actingAs($admin)->get(route('global.search', ['query' => 'PO-ADMIN-1']));
        $response->assertStatus(200);
        $response->assertSee('PO-ADMIN-1');
    }

    public function test_global_search_orders_ledgers_correctly()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $warehouse = Warehouse::create([
            'name' => 'Sort Warehouse',
            'type' => 'CENTRAL',
            'status' => 'ACTIVE',
        ]);

        $item = Item::create([
            'name' => 'Sort Item',
            'type' => 'BULK',
            'unit' => 'PCS',
        ]);

        // Same entry_date, created_at, different id
        $ledger1 = Ledger::create([
            'entry_date' => '2026-05-01',
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-SORT-TEST',
            'status' => 'PENDING',
            'created_at' => '2026-05-01 10:00:00',
        ]);

        $ledger2 = Ledger::create([
            'entry_date' => '2026-05-01',
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-SORT-TEST',
            'status' => 'PENDING',
            'created_at' => '2026-05-01 10:00:00',
        ]);

        // Newer entry_date
        $ledger3 = Ledger::create([
            'entry_date' => '2026-05-02',
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 3,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-SORT-TEST',
            'status' => 'PENDING',
            'created_at' => '2026-05-01 09:00:00',
        ]);

        // Same entry_date as ledger1/2, but newer created_at
        $ledger4 = Ledger::create([
            'entry_date' => '2026-05-01',
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 4,
            'warehouse_id' => $warehouse->id,
            'po_number' => 'PO-SORT-TEST',
            'status' => 'PENDING',
            'created_at' => '2026-05-01 11:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('global.search', ['query' => 'PO-SORT-TEST']));
        $response->assertStatus(200);

        $response->assertViewHas('ledgers', function ($ledgers) use ($ledger1, $ledger2, $ledger3, $ledger4) {
            $ids = $ledgers->pluck('id')->all();
            // Expected order:
            // 1. ledger3 (latest entry_date: 2026-05-02)
            // 2. ledger4 (entry_date: 2026-05-01, latest created_at: 11:00:00)
            // 3. ledger2 (entry_date: 2026-05-01, created_at: 10:00:00, higher id)
            // 4. ledger1 (entry_date: 2026-05-01, created_at: 10:00:00, lower id)
            return $ids === [$ledger3->id, $ledger4->id, $ledger2->id, $ledger1->id];
        });
    }
}
