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
        $response->assertViewMissing('allItems');
    }

    public function test_admin_can_search_merge_targets_via_autocomplete()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);
        $item2 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement Extra',
            'specification' => '40kg',
            'unit' => 'Bags',
        ]);

        // Search matching "Deformed"
        $response = $this->actingAs($admin)->get(route('items.merge.search', $item2) . '?q=Deformed');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $item1->id,
            'name' => 'Deformed Bar',
            'specification' => '16mm x 6m',
            'unit' => 'length',
        ]);

        // Search matching combination: "Cement Extra 40kg"
        $response = $this->actingAs($admin)->get(route('items.merge.search', $item1) . '?q=Cement Extra 40kg');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $item2->id,
            'name' => 'Cement Extra',
            'specification' => '40kg',
            'unit' => 'Bags',
        ]);
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

    public function test_logger_can_access_item_creation_page()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $response = $this->actingAs($logger)->get(route('logger.items.create'));
        $response->assertStatus(200);
        $response->assertViewIs('logger.items.create');
    }

    public function test_logger_can_create_item_without_similar()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Brand New Rare Item',
            'specification' => 'None',
            'unit' => 'Boxes',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('items', [
            'name' => 'Brand New Rare Item',
            'is_approved' => false,
        ]);
    }

    public function test_logger_cannot_create_item_if_exact_match_exists()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Exact Item Match',
            'specification' => '100g',
            'unit' => 'g',
        ]);

        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Exact Item Match',
            'specification' => '100g',
            'unit' => 'g',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_logger_prompted_for_similar_items_if_like_match_exists()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Super Steel Pipe 10m',
            'specification' => 'Heavy',
            'unit' => 'Meters',
        ]);

        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Super Steel Pipe 10m',
            'specification' => 'Light', // similar but not exact
            'unit' => 'Meters',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('logger.items.confirm');
        $response->assertViewHas('similarItems');
    }

    public function test_logger_can_proceed_and_create_item_from_confirmation()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Super Steel Pipe 10m',
            'specification' => 'Heavy',
            'unit' => 'Meters',
        ]);

        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Super Steel Pipe 10m',
            'specification' => 'Light',
            'unit' => 'Meters',
            'confirm' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('items', [
            'name' => 'Super Steel Pipe 10m',
            'specification' => 'Light',
            'is_approved' => false,
        ]);
    }

    public function test_supervisor_can_approve_unapproved_item()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Logger Item',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($supervisor)->post(route('items.approve', $item));

        $response->assertRedirect();
        $this->assertTrue($item->fresh()->is_approved);
    }

    public function test_admin_can_access_items_review_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Logger Item',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.items.review'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.items.review');
        $response->assertViewHas('items');
        $response->assertSee('Logger Item');
    }

    public function test_non_admin_cannot_access_items_review_page()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $response = $this->actingAs($supervisor)->get(route('admin.items.review'));
        $response->assertStatus(403);

        $response = $this->actingAs($logger)->get(route('admin.items.review'));
        $response->assertStatus(403);
    }

    public function test_items_review_page_only_lists_unapproved_items()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Unapproved Item',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Approved Item',
            'unit' => 'Pcs',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.items.review'));

        $response->assertStatus(200);
        $response->assertSee('Unapproved Item');
        $response->assertDontSee('Approved Item');
    }

    public function test_items_review_page_filtering_works()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Unapproved Consumable',
            'specification' => 'Spec A',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);
        Item::create([
            'type' => 'ASSET',
            'name' => 'Unapproved Asset',
            'specification' => 'Spec B',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);

        // Filter by Search
        $response = $this->actingAs($admin)->get(route('admin.items.review') . '?search=Consumable');
        $response->assertSee('Unapproved Consumable');
        $response->assertDontSee('Unapproved Asset');

        // Filter by Type
        $response = $this->actingAs($admin)->get(route('admin.items.review') . '?type=ASSET');
        $response->assertSee('Unapproved Asset');
        $response->assertDontSee('Unapproved Consumable');
    }

    public function test_navigation_displays_pending_items_badge_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get(route('home'));
        $response->assertSee('Pending Items');
        $response->assertDontSee('badge bg-danger rounded-pill'); // initially hidden when 0
        
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Logger Item',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('home'));
        $response->assertSee('Pending Items');
        $response->assertSee('1'); // badge count
    }
}
