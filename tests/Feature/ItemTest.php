<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_an_item_soft_delete()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'To Be Deleted',
            'specification' => 'Spec',
            'unit' => 'Pcs',
        ]);

        $response = $this->actingAs($admin)->delete(route('items.destroy', $item));

        $response->assertRedirect(route('items.index'));
        $this->assertSoftDeleted('items', [
            'id' => $item->id,
        ]);
    }

    public function test_non_admin_cannot_delete_an_item()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Should Not Be Deleted',
            'specification' => 'Spec',
            'unit' => 'Pcs',
        ]);

        $response = $this->actingAs($supervisor)->delete(route('items.destroy', $item));

        $response->assertStatus(403);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_item_does_not_break_ledger()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement',
            'unit' => 'Bags',
        ]);
        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Create a ledger entry referencing the item
        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial stock',
        ]);

        // Delete the item
        $item->delete();

        // Ensure the item is soft-deleted
        $this->assertSoftDeleted('items', ['id' => $item->id]);

        // Load ledger with item and make sure it does not crash or return null
        $loadedLedger = Ledger::with('item')->find($ledger->id);
        
        $this->assertNotNull($loadedLedger);
        $this->assertNotNull($loadedLedger->item);
        $this->assertEquals('Cement', $loadedLedger->item->name);
    }

    public function test_cannot_create_duplicate_item_with_same_name_spec_unit()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(1, Item::count());
    }

    public function test_cannot_update_item_to_create_duplicate_with_same_name_spec_unit()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar 1',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        $item2 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar 2',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        $response = $this->actingAs($supervisor)->put(route('items.update', $item2), [
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar 1', // Change name to match item1, creating a duplicate
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        $response->assertSessionHasErrors([
            'name' => "An item with this exact name, specification, and unit already exists. (ID: {$item1->id}, Name: Deformed Bar 1, Specification: 16mm x 6m, Unit: length)"
        ]);
        $this->assertEquals('Deformed Bar 2', $item2->fresh()->name); // Should remain unchanged
    }

    public function test_non_admin_cannot_access_merge_routes()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Item 1',
            'unit' => 'Pcs',
        ]);
        $item2 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Item 2',
            'unit' => 'Pcs',
        ]);

        $response = $this->actingAs($supervisor)->get(route('items.merge.form', $item1));
        $response->assertStatus(403);

        $response = $this->actingAs($supervisor)->post(route('items.merge', $item1), [
            'target_item_id' => $item2->id,
            'confirm_merge' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_access_merge_form()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Item 1',
            'unit' => 'Pcs',
        ]);

        $response = $this->actingAs($admin)->get(route('items.merge.form', $item1));
        $response->assertStatus(200);
        $response->assertViewHas('item');
        $response->assertViewHas('ledgerCount');
        $response->assertViewHas('allItems');
    }

    public function test_admin_can_merge_items()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Item 1 (Wrong)',
            'unit' => 'Pcs',
        ]);
        $item2 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Item 2 (Correct)',
            'unit' => 'Pcs',
        ]);

        $warehouse = Warehouse::create(['type' => 'CENTRAL', 'name' => 'Main', 'status' => 'ACTIVE']);

        // Create a ledger entry referencing the wrong item
        $ledger = Ledger::create([
            'entry_date' => now(),
            'type' => 'IN',
            'action' => 'INITIAL_STOCK',
            'item_id' => $item1->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'remarks' => 'Initial stock',
        ]);

        // Merge item1 (wrong) into item2 (correct)
        $response = $this->actingAs($admin)->post(route('items.merge', $item1), [
            'target_item_id' => $item2->id,
            'confirm_merge' => '1',
        ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('success');

        // Verify ledger has been updated to the correct item
        $this->assertEquals($item2->id, $ledger->fresh()->item_id);

        // Verify the source item is soft-deleted
        $this->assertSoftDeleted('items', ['id' => $item1->id]);
    }
}
