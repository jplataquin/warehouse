<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ItemCategory;
use App\Models\ItemType;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_admins_can_view_item_categories_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ItemCategory::create(['name' => 'Heavy Equipment']);

        $response = $this->actingAs($admin)->get(route('item-categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Heavy Equipment');
    }

    /** @test */
    public function test_non_admins_cannot_view_item_categories_list()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $logger = User::factory()->create(['role' => 'logger']);

        $response1 = $this->actingAs($supervisor)->get(route('item-categories.index'));
        $response1->assertStatus(403);

        $response2 = $this->actingAs($logger)->get(route('item-categories.index'));
        $response2->assertStatus(403);
    }

    /** @test */
    public function test_admins_can_create_item_category()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('item-categories.store'), [
            'name' => 'Power Tools',
        ]);

        $response->assertRedirect(route('item-categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_categories', [
            'name' => 'Power Tools',
        ]);
    }

    /** @test */
    public function test_item_category_name_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ItemCategory::create(['name' => 'Power Tools']);

        $response = $this->actingAs($admin)->post(route('item-categories.store'), [
            'name' => 'Power Tools',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function test_admins_can_edit_item_category()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ItemCategory::create(['name' => 'Tools']);

        $response = $this->actingAs($admin)->put(route('item-categories.update', $category->id), [
            'name' => 'Hand Tools',
        ]);

        $response->assertRedirect(route('item-categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('item_categories', [
            'id' => $category->id,
            'name' => 'Hand Tools',
        ]);
    }

    /** @test */
    public function test_cannot_delete_item_category_in_use()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ItemCategory::create(['name' => 'Vehicles']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();

        // Assign to an item
        Item::create([
            'item_type_id' => $assetType->id,
            'category_id' => $category->id,
            'name' => 'Bulldozer D6',
            'specification' => 'CAT',
            'unit' => 'unit',
        ]);

        $response = $this->actingAs($admin)->delete(route('item-categories.destroy', $category->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete item category because it is currently assigned to one or more items.');
        $this->assertDatabaseHas('item_categories', ['id' => $category->id]);
    }

    /** @test */
    public function test_can_delete_unused_item_category()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ItemCategory::create(['name' => 'Office Furniture']);

        $response = $this->actingAs($admin)->delete(route('item-categories.destroy', $category->id));

        $response->assertRedirect(route('item-categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('item_categories', ['id' => $category->id]);
    }
}
