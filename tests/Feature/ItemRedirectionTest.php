<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemRedirectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_link_preserves_search_and_filter_query_params()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement Extra',
            'specification' => '40kg',
            'unit' => 'Bags'
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('items.index', [
                'search' => 'Cement',
                'type' => 'CONSUMABLE'
            ]));

        $response->assertStatus(200);
        
        // Assert that the edit button link contains the query parameters
        $expectedEditUrl = route('items.edit', [
            'item' => $item->id,
            'search' => 'Cement',
            'type' => 'CONSUMABLE'
        ]);
        
        $response->assertSee(e($expectedEditUrl), false);
    }

    public function test_edit_view_preserves_query_params_in_form_action_and_cancel_button()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement Extra',
            'specification' => '40kg',
            'unit' => 'Bags'
        ]);

        $response = $this->actingAs($supervisor)
            ->get(route('items.edit', [
                'item' => $item->id,
                'search' => 'Cement',
                'type' => 'CONSUMABLE',
                'page' => 2
            ]));

        $response->assertStatus(200);

        // Assert form action contains the query parameters
        $expectedUpdateUrl = route('items.update', [
            'item' => $item->id,
            'search' => 'Cement',
            'type' => 'CONSUMABLE',
            'page' => 2
        ]);
        $response->assertSee(e($expectedUpdateUrl), false);

        // Assert Cancel button contains the query parameters
        $expectedCancelUrl = route('items.index', [
            'search' => 'Cement',
            'type' => 'CONSUMABLE',
            'page' => 2
        ]);
        $response->assertSee(e($expectedCancelUrl), false);
    }

    public function test_updating_item_redirects_back_to_list_with_query_params()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement Extra',
            'specification' => '40kg',
            'unit' => 'Bags'
        ]);

        // Send put request with query parameters (which should be in the route/action URL)
        $response = $this->actingAs($supervisor)
            ->put(route('items.update', [
                'item' => $item->id,
                'search' => 'Cement',
                'type' => 'CONSUMABLE',
                'page' => 2
            ]), [
                'name' => 'Cement Extra Premium',
                'type' => 'CONSUMABLE',
                'specification' => '50kg',
                'unit' => 'Bags'
            ]);

        $expectedRedirectUrl = route('items.index', [
            'search' => 'Cement',
            'type' => 'CONSUMABLE',
            'page' => 2
        ]);

        $response->assertRedirect($expectedRedirectUrl);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Cement Extra Premium',
            'specification' => '50kg'
        ]);
    }

    public function test_pagination_renders_using_bootstrap_five_styling()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        // Create 55 items to force pagination (since pagination limit is 50)
        for ($i = 1; $i <= 55; $i++) {
            Item::create([
                'type' => 'CONSUMABLE',
                'name' => "Cement Block {$i}",
                'specification' => 'Standard',
                'unit' => 'Pieces'
            ]);
        }

        $response = $this->actingAs($supervisor)
            ->get(route('items.index'));

        $response->assertStatus(200);
        
        // Verify bootstrap 5 pagination classes are rendered on the page
        $response->assertSee('class="pagination"', false);
        $response->assertSee('class="page-item', false);
        $response->assertSee('class="page-link"', false);
    }
}
