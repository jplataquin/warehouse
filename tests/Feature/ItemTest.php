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

    public function test_items_index_page_pending_tab_only_lists_unapproved_items()
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
            'name' => 'Some Approved Product',
            'unit' => 'Pcs',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('items.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Unapproved Item');
        $response->assertDontSee('Some Approved Product');
    }

    public function test_navigation_displays_pending_items_badge_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get(route('home'));
        $response->assertSee('Items');
        $response->assertDontSee('badge bg-danger rounded-pill'); // initially hidden when 0
        
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Logger Item',
            'unit' => 'Pcs',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('home'));
        $response->assertSee('Items');
        $response->assertSee('1'); // badge count
    }

    public function test_supervisor_can_create_item_with_photo_file()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('item.jpg', 100);

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Drill DCD771',
            'specification' => '20V Max',
            'unit' => 'Pcs',
            'photo_file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $item = Item::where('name', 'Drill DCD771')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->photo);
        
        // Assert file exists in storage
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($item->photo);
    }

    public function test_supervisor_can_create_item_with_photo_url_download()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Http::fake([
            'https://example.com/test-drill.jpg' => \Illuminate\Support\Facades\Http::response('fake_image_binary', 200, [
                'Content-Type' => 'image/jpeg',
            ])
        ]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Drill DCD771',
            'specification' => '20V Max',
            'unit' => 'Pcs',
            'photo_url' => 'https://example.com/test-drill.jpg',
        ]);

        $response->assertRedirect(route('items.index'));
        $item = Item::where('name', 'Drill DCD771')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->photo);
        
        // Assert file was downloaded and saved
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($item->photo);
        $this->assertEquals('fake_image_binary', \Illuminate\Support\Facades\Storage::disk('public')->get($item->photo));
    }

    public function test_image_search_endpoint_returns_json_results()
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://pixabay.com/api/*' => \Illuminate\Support\Facades\Http::response([
                'hits' => [
                    [
                        'tags' => 'Test Image',
                        'webformatURL' => 'https://example.com/test.jpg',
                        'previewURL' => 'https://example.com/thumb.jpg',
                    ]
                ]
            ], 200)
        ]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->get(route('items.search-images', ['query' => 'Drill']));

        $response->assertStatus(200);
        $response->assertJson([
            [
                'title' => 'Test Image',
                'link' => 'https://example.com/test.jpg',
                'thumbnail' => 'https://example.com/thumb.jpg',
            ]
        ]);
    }

    public function test_similar_items_endpoint_returns_view_with_similar_items()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $item1 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar 16mm',
            'specification' => '16mm x 6m',
            'unit' => 'length',
            'is_approved' => true,
        ]);

        $item2 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Deformed Bar',
            'specification' => '',
            'unit' => 'length',
            'is_approved' => false,
        ]);

        $item3 = Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Completely Different Cement',
            'specification' => 'Portland',
            'unit' => 'Bags',
            'is_approved' => true,
        ]);

        // Request similar items for item2 (should find item1 since item2's concat is "Deformed Bar" and item1's concat matches it)
        $response = $this->actingAs($supervisor)->get(route('items.similar', $item2));

        $response->assertStatus(200);
        $response->assertViewIs('supervisor.items._similar');
        $response->assertViewHas('similarItems');
        
        $similarItems = $response->viewData('similarItems');
        $this->assertTrue($similarItems->contains($item1));
        $this->assertFalse($similarItems->contains($item3));
        $this->assertFalse($similarItems->contains($item2)); // should exclude itself
    }

    public function test_chunk_upload_flow_works_correctly()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        // Chunk 1
        $file1 = \Illuminate\Http\UploadedFile::fake()->create('chunk1.jpg', 10);
        $response1 = $this->actingAs($supervisor)->post(route('chunk.upload'), [
            'file_id' => 'test_upload_123',
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file_name' => 'test.jpg',
            'file_chunk' => $file1,
        ]);
        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Chunk 2
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('chunk2.jpg', 10);
        $response2 = $this->actingAs($supervisor)->post(route('chunk.upload'), [
            'file_id' => 'test_upload_123',
            'chunk_index' => 1,
            'total_chunks' => 2,
            'file_name' => 'test.jpg',
            'file_chunk' => $file2,
        ]);
        $response2->assertStatus(200);
        $response2->assertJsonStructure(['success', 'temp_file_name']);
        
        $tempFileName = $response2->json('temp_file_name');
        
        // Submit item form with temp_photo_file
        $responseStore = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Chunked Item Test',
            'unit' => 'Pcs',
            'temp_photo_file' => $tempFileName,
        ]);
        
        $responseStore->assertRedirect(route('items.index'));
        $item = Item::where('name', 'Chunked Item Test')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->photo);
        
        // Check file exists on public disk
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($item->photo);
    }

    public function test_uploads_cleanup_command_works_correctly()
    {
        $tempDir = storage_path('app/temp_uploads');
        if (!\Illuminate\Support\Facades\File::exists($tempDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($tempDir, 0755, true, true);
        }
        
        // Create a fake stale file
        $staleFile = $tempDir . '/stale_file.png';
        file_put_contents($staleFile, 'stale content');
        touch($staleFile, time() - 36 * 3600); // 36 hours ago
        
        // Create a fake fresh file
        $freshFile = $tempDir . '/fresh_file.png';
        file_put_contents($freshFile, 'fresh content');
        
        clearstatcache();
        
        $this->artisan('uploads:cleanup --hours=24')
            ->assertExitCode(0);
            
        $this->assertFalse(\Illuminate\Support\Facades\File::exists($staleFile));
        $this->assertTrue(\Illuminate\Support\Facades\File::exists($freshFile));
        
        // Clean up fresh file
        \Illuminate\Support\Facades\File::delete($freshFile);
    }

    public function test_supervisor_uploaded_photo_file_is_converted_to_webp()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        
        $file = \Illuminate\Http\UploadedFile::fake()->image('item.jpg');

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Drill DCD771 Webp',
            'specification' => '20V Max',
            'unit' => 'Pcs',
            'photo_file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $item = Item::where('name', 'Drill DCD771 Webp')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->photo);
        
        $this->assertStringEndsWith('.webp', $item->photo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($item->photo);
        
        $content = \Illuminate\Support\Facades\Storage::disk('public')->get($item->photo);
        $this->assertStringStartsWith('RIFF', $content);
        $this->assertStringContainsString('WEBP', $content);
    }

    public function test_supervisor_downloaded_photo_url_is_converted_to_webp()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        
        ob_start();
        $img = imagecreatetruecolor(10, 10);
        imagejpeg($img);
        $jpegBinary = ob_get_clean();
        imagedestroy($img);

        \Illuminate\Support\Facades\Http::fake([
            'https://example.com/test-drill.jpg' => \Illuminate\Support\Facades\Http::response($jpegBinary, 200, [
                'Content-Type' => 'image/jpeg',
            ])
        ]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $response = $this->actingAs($supervisor)->post(route('items.store'), [
            'type' => 'CONSUMABLE',
            'name' => 'Drill DCD771 Webp URL',
            'specification' => '20V Max',
            'unit' => 'Pcs',
            'photo_url' => 'https://example.com/test-drill.jpg',
        ]);

        $response->assertRedirect(route('items.index'));
        $item = Item::where('name', 'Drill DCD771 Webp URL')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->photo);
        
        $this->assertStringEndsWith('.webp', $item->photo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($item->photo);
        
        $content = \Illuminate\Support\Facades\Storage::disk('public')->get($item->photo);
        $this->assertStringStartsWith('RIFF', $content);
        $this->assertStringContainsString('WEBP', $content);
    }
}
