<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumable_item_shows_up_in_create_ledger_view()
    {
        $user = User::factory()->create(['role' => 'logger']);
        $item = Item::create([
            'name' => 'Consumable Item Test',
            'type' => 'CONSUMABLE',
            'unit' => 'Units',
        ]);
        $warehouse = Warehouse::create(['name' => 'Test WH', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $user->warehouses()->attach($warehouse);

        $response = $this->actingAs($user)->get(route('ledgers.create', ['warehouse_id' => $warehouse->id]));

        $response->assertStatus(200);
        $response->assertSee('Consumable Item Test Units (CONSUMABLE)');
    }

    public function test_admin_can_search_items_in_ledger_after_selecting_warehouse()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);

        $item1 = Item::create([
            'name' => 'Heavy Excavator',
            'type' => 'ASSET',
            'unit' => 'UNIT',
        ]);

        $item2 = Item::create([
            'name' => 'Safety Gloves',
            'type' => 'CONSUMABLE',
            'unit' => 'PAIR',
        ]);

        // Put both items in the warehouse using ledger entries
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item1->id,
            'quantity' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO1',
            'delivery_receipt' => 'DR1',
        ]);

        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item2->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO2',
            'delivery_receipt' => 'DR2',
        ]);

        // 1. Visit without search
        $response = $this->actingAs($admin)->get(route('ledgers.index', ['warehouse_id' => $warehouse->id]));
        $response->assertStatus(200);
        $response->assertSee('Heavy Excavator');
        $response->assertSee('Safety Gloves');
        $response->assertSee('Search Item');

        // 2. Visit with search for 'Excavator'
        $response = $this->actingAs($admin)->get(route('ledgers.index', [
            'warehouse_id' => $warehouse->id,
            'item_search' => 'Excavator',
        ]));
        $response->assertStatus(200);
        $response->assertSee('Heavy Excavator');
        $response->assertDontSee('Safety Gloves');

        // 3. Visit with search for 'Gloves'
        $response = $this->actingAs($admin)->get(route('ledgers.index', [
            'warehouse_id' => $warehouse->id,
            'item_search' => 'Gloves',
        ]));
        $response->assertStatus(200);
        $response->assertDontSee('Heavy Excavator');
        $response->assertSee('Safety Gloves');
    }

    public function test_sub_warehouse_text_format_in_ledger_warehouse_select()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Warehouse::create(['name' => 'Parent Warehouse', 'type' => 'CENTRAL', 'status' => 'ACTIVE']);
        $sub = Warehouse::create([
            'name' => 'Sub Warehouse',
            'type' => 'SITE',
            'status' => 'ACTIVE',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($admin)->get(route('ledgers.index'));

        $response->assertStatus(200);
        // It should display Parent Warehouse > Sub Warehouse for the sub warehouse
        $response->assertSee('Parent Warehouse &gt; Sub Warehouse', false);
    }
}
