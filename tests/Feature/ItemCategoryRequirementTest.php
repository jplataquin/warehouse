<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCategoryRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_item_requires_category_on_create()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'type' => $assetType->id,
            'name' => 'Generator 5KW',
            'specification' => 'Diesel',
            'unit' => 'unit',
            // category_id missing
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('items', [
            'name' => 'Generator 5KW',
        ]);
    }

    public function test_consumable_item_does_not_require_category_on_create()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $consumableType = ItemType::where('base_behavior', 'CONSUMABLE')->first();

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'type' => $consumableType->id,
            'name' => 'Portland Cement',
            'specification' => '40kg',
            'unit' => 'bag',
        ]);

        $response->assertSessionDoesntHaveErrors('category_id');
        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'name' => 'Portland Cement',
            'category_id' => null,
        ]);
    }

    public function test_asset_item_succeeds_with_valid_category_on_create()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();
        $category = ItemCategory::create(['name' => 'Heavy Machinery']);

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'type' => $assetType->id,
            'category_id' => $category->id,
            'name' => 'Excavator Cat 320',
            'specification' => 'Standard',
            'unit' => 'unit',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'name' => 'Excavator Cat 320',
            'category_id' => $category->id,
        ]);
    }

    public function test_asset_item_requires_category_on_update()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();
        $category = ItemCategory::create(['name' => 'Machinery']);

        $item = Item::create([
            'item_type_id' => $assetType->id,
            'category_id' => $category->id,
            'name' => 'Forklift 3T',
            'unit' => 'unit',
        ]);

        $response = $this->actingAs($admin)->put(route('items.update', $item), [
            'type' => $assetType->id,
            'category_id' => '', // cleared
            'name' => 'Forklift 3T Updated',
            'unit' => 'unit',
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Forklift 3T',
        ]);
    }

    public function test_logger_asset_item_requires_category_on_create()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();

        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => $assetType->id,
            'name' => 'Power Drill 18V',
            'unit' => 'pcs',
            // no category_id
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_logger_asset_item_succeeds_with_category()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $assetType = ItemType::where('base_behavior', 'ASSET')->first();
        $category = ItemCategory::create(['name' => 'Power Tools']);

        $response = $this->actingAs($logger)->post(route('logger.items.store'), [
            'type' => $assetType->id,
            'category_id' => $category->id,
            'name' => 'Power Drill 18V',
            'unit' => 'pcs',
            'confirm' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('items', [
            'name' => 'Power Drill 18V',
            'category_id' => $category->id,
            'is_approved' => false,
        ]);
    }

    public function test_create_and_edit_views_pass_categories()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ItemCategory::create(['name' => 'Vehicles']);
        $item = Item::create([
            'type' => 'ASSET',
            'category_id' => $category->id,
            'name' => 'Dump Truck',
            'unit' => 'unit',
        ]);

        $responseCreate = $this->actingAs($admin)->get(route('items.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertViewHas('categories');
        $responseCreate->assertSee('Vehicles');

        $responseEdit = $this->actingAs($admin)->get(route('items.edit', $item));
        $responseEdit->assertStatus(200);
        $responseEdit->assertViewHas('categories');
        $responseEdit->assertSee('Vehicles');
    }
}
