<?php

namespace Tests\Feature;

use App\Models\Item;
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
}
