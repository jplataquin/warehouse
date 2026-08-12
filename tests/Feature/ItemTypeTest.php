<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ItemType;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admins_can_view_item_types_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Default types 'Consumable' and 'Asset' are seeded by the migrations
        $response = $this->actingAs($admin)->get(route('item-types.index'));

        $response->assertStatus(200);
        $response->assertSee('Consumable');
        $response->assertSee('Asset');
    }

    /** @test */
    public function test_non_admins_cannot_view_item_types_list()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $response1 = $this->actingAs($supervisor)->get(route('item-types.index'));
        $response1->assertStatus(403);

        $response2 = $this->actingAs($logger)->get(route('item-types.index'));
        $response2->assertStatus(403);
    }

    /** @test */
    public function test_admins_can_create_custom_item_type()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('item-types.store'), [
            'name' => 'IT Equipment',
            'base_behavior' => 'ASSET',
        ]);

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_types', [
            'name' => 'IT Equipment',
            'base_behavior' => 'ASSET',
        ]);
    }

    /** @test */
    public function test_admins_can_edit_custom_item_type()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customType = ItemType::create([
            'name' => 'Power Tools',
            'base_behavior' => 'CONSUMABLE',
        ]);

        $response = $this->actingAs($admin)->put(route('item-types.update', $customType->id), [
            'name' => 'Heavy Power Tools',
            'base_behavior' => 'ASSET',
        ]);

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_types', [
            'id' => $customType->id,
            'name' => 'Heavy Power Tools',
            'base_behavior' => 'ASSET',
        ]);
    }

    /** @test */
    public function test_cannot_delete_default_item_types()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $consumableType = ItemType::where('name', 'Consumable')->first();

        $response = $this->actingAs($admin)->delete(route('item-types.destroy', $consumableType->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete default system item types.');
        $this->assertDatabaseHas('item_types', ['id' => $consumableType->id]);
    }

    /** @test */
    public function test_cannot_delete_item_type_in_use()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customType = ItemType::create([
            'name' => 'Vehicles',
            'base_behavior' => 'ASSET',
        ]);

        // Assign to an item
        Item::create([
            'item_type_id' => $customType->id,
            'name' => 'Excavator 101',
            'specification' => 'Yellow',
            'unit' => 'pcs',
        ]);

        $response = $this->actingAs($admin)->delete(route('item-types.destroy', $customType->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete item type because it is currently assigned to one or more items.');
        $this->assertDatabaseHas('item_types', ['id' => $customType->id]);
    }

    /** @test */
    public function test_can_delete_unused_custom_item_type()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customType = ItemType::create([
            'name' => 'Office Supplies',
            'base_behavior' => 'CONSUMABLE',
        ]);

        $response = $this->actingAs($admin)->delete(route('item-types.destroy', $customType->id));

        $response->assertRedirect(route('item-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('item_types', ['id' => $customType->id]);
    }

    /** @test */
    public function test_can_create_item_with_custom_item_type()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $customType = ItemType::create([
            'name' => 'Plumbing',
            'base_behavior' => 'CONSUMABLE',
        ]);

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => $customType->id,
            'name' => 'PVC Pipe 2 inch',
            'specification' => 'Schedule 40',
            'unit' => 'meters',
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'item_type_id' => $customType->id,
            'name' => 'PVC Pipe 2 inch',
            'specification' => 'Schedule 40',
            'unit' => 'meters',
        ]);
    }
}
