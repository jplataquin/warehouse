<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_stock_returns_correct_balance()
    {
        $user = User::factory()->create();
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $project = Project::create(['name' => 'Test Project']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'type' => 'CENTRAL', 'status' => 'ACTIVE', 'project_id' => $project->id]);

        $allocation = Allocation::create([
            'name' => 'Test Alloc',
            'warehouse_id' => $warehouse->id,
        ]);

        // Add 100 bags (Approved)
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 100,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
        ]);

        // Add 50 bags (Pending) - Should NOT count
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 50,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'po_number' => 'PO-2',
            'delivery_receipt' => 'DR-2',
        ]);

        // Subtract 30 bags (Approved)
        Ledger::create([
            'type' => 'OUT',
            'action' => 'ALLOCATE',
            'item_id' => $item->id,
            'quantity' => 30,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'allocation_id' => $allocation->id,
        ]);

        $response = $this->actingAs($user)->get("/items/{$item->id}/stock?warehouse_id={$warehouse->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 120, // 100 (In) + 50 (In) - 30 (Out) = 120
            'unit' => 'Bags',
        ]);
    }

    public function test_get_stock_without_warehouse_id_returns_global_balance()
    {
        $user = User::factory()->create();
        $item = Item::create(['type' => 'CONSUMABLE', 'name' => 'Cement', 'unit' => 'Bags']);
        $warehouse1 = Warehouse::create(['name' => 'WH 1', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $warehouse2 = Warehouse::create(['name' => 'WH 2', 'type' => 'SITE', 'status' => 'ACTIVE']);

        Ledger::create([
            'type' => 'IN', 'action' => 'DELIVERY', 'item_id' => $item->id, 'quantity' => 100,
            'warehouse_id' => $warehouse1->id, 'status' => 'APPROVED', 'po_number' => 'P1', 'delivery_receipt' => 'D1',
        ]);

        Ledger::create([
            'type' => 'IN', 'action' => 'DELIVERY', 'item_id' => $item->id, 'quantity' => 50,
            'warehouse_id' => $warehouse2->id, 'status' => 'APPROVED', 'po_number' => 'P2', 'delivery_receipt' => 'D2',
        ]);

        $response = $this->actingAs($user)->get("/items/{$item->id}/stock");

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 150,
            'unit' => 'Bags',
        ]);
    }
}
