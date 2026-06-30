<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_generate_and_revoke_public_token()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main central WH',
            'status' => 'ACTIVE',
        ]);

        $this->assertNull($warehouse->public_token);

        // Generate Token
        $response = $this->actingAs($supervisor)
            ->post(route('warehouses.public_token.generate', $warehouse));

        $response->assertRedirect(route('warehouses.show', $warehouse));
        $warehouse->refresh();
        $this->assertNotNull($warehouse->public_token);
        $this->assertEquals(32, strlen($warehouse->public_token));

        // Revoke Token
        $response = $this->actingAs($supervisor)
            ->post(route('warehouses.public_token.revoke', $warehouse));

        $response->assertRedirect(route('warehouses.show', $warehouse));
        $warehouse->refresh();
        $this->assertNull($warehouse->public_token);
    }

    public function test_guest_cannot_generate_or_revoke_public_token()
    {
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main central WH',
            'status' => 'ACTIVE',
        ]);

        // Try generating
        $response = $this->post(route('warehouses.public_token.generate', $warehouse));
        $response->assertRedirect(route('login'));

        // Try revoking
        $response = $this->post(route('warehouses.public_token.revoke', $warehouse));
        $response->assertRedirect(route('login'));
    }

    public function test_anyone_can_view_public_dashboard_with_valid_token()
    {
        $project = Project::create(['name' => 'Test Project']);
        $warehouse = Warehouse::create([
            'project_id' => $project->id,
            'type' => 'CENTRAL',
            'name' => 'Public WH',
            'status' => 'ACTIVE',
            'public_token' => 'test-valid-public-token-12345',
        ]);

        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Public Cement',
            'specification' => '40kg',
            'unit' => 'Bags',
        ]);

        // Put 50 bags in stock
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 50,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
        ]);

        $response = $this->get(route('public.warehouse.dashboard', 'test-valid-public-token-12345'));

        $response->assertStatus(200);
        $response->assertSee('Public WH Public Stock Dashboard');
        $response->assertSee('Public Cement');
        $response->assertSee('50'); // stock quantity

        // Make sure sensitive actions are NOT visible on public view
        $response->assertDontSee('New Entry');
        $response->assertDontSee('View History');
        $response->assertDontSee('Edit Warehouse');
    }

    public function test_public_dashboard_returns_404_for_invalid_or_revoked_token()
    {
        $response = $this->get(route('public.warehouse.dashboard', 'non-existent-token'));
        $response->assertStatus(404);
    }

    public function test_anyone_can_check_item_stock_with_valid_token()
    {
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Public WH',
            'status' => 'ACTIVE',
            'public_token' => 'test-valid-public-token-12345',
        ]);

        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Public Cement',
            'specification' => '40kg',
            'unit' => 'Bags',
        ]);

        // Put 75 bags in stock
        Ledger::create([
            'type' => 'IN',
            'action' => 'DELIVERY',
            'item_id' => $item->id,
            'quantity' => 75,
            'warehouse_id' => $warehouse->id,
            'status' => 'APPROVED',
            'po_number' => 'PO-1',
            'delivery_receipt' => 'DR-1',
        ]);

        // Fetch stock with token
        $response = $this->get(route('public.items.stock', [
            'item' => $item->id,
            'token' => 'test-valid-public-token-12345',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 75,
            'unit' => 'Bags',
        ]);
    }

    public function test_checking_item_stock_requires_valid_token()
    {
        $warehouse = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Public WH',
            'status' => 'ACTIVE',
            'public_token' => 'test-valid-public-token-12345',
        ]);

        $item = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Public Cement',
            'specification' => '40kg',
            'unit' => 'Bags',
        ]);

        // Without token
        $response = $this->get(route('public.items.stock', $item->id));
        $response->assertStatus(401);

        // With invalid token
        $response = $this->get(route('public.items.stock', [
            'item' => $item->id,
            'token' => 'invalid-token',
        ]));
        $response->assertStatus(404);
    }
}
