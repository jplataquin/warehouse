<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_logger_cannot_access_import_form()
    {
        $logger = User::factory()->create(['role' => 'logger']);
        $response = $this->actingAs($logger)->get(route('items.import.form'));
        $response->assertStatus(403);
    }

    public function test_supervisor_can_access_import_form()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $response = $this->actingAs($supervisor)->get(route('items.import.form'));
        $response->assertStatus(200);
        $response->assertSee('Bulk Item Import');
    }

    public function test_preview_parses_csv_data()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $content = "type,name,specification,unit\n";
        $content .= "CONSUMABLE,Cement,40kg,Bags\n";
        $content .= "ASSET,Drill,Brushless,Units\n";

        $file = UploadedFile::fake()->createWithContent('items.csv', $content);

        $response = $this->actingAs($supervisor)->post(route('items.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Cement');
        $response->assertSee('40kg');
        $response->assertSee('Drill');
        $this->assertNotNull(session('item_import_data'));
    }

    public function test_store_saves_valid_items()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $importData = [
            [
                'type' => 'CONSUMABLE',
                'name' => 'Imported Item 1',
                'specification' => 'Spec 1',
                'unit' => 'Unit 1',
                'is_valid' => true,
                'row_number' => 2,
            ],
            [
                'type' => 'ASSET',
                'name' => 'Imported Item 2',
                'specification' => 'Spec 2',
                'unit' => 'Unit 2',
                'is_valid' => true,
                'row_number' => 3,
            ],
        ];

        session(['item_import_data' => $importData]);

        $response = $this->actingAs($supervisor)->post(route('items.import.store'));

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', ['name' => 'Imported Item 1']);
        $this->assertDatabaseHas('items', ['name' => 'Imported Item 2']);
    }

    public function test_uniqueness_is_enforced_in_preview()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Existing Item',
            'specification' => 'Existing Spec',
            'unit' => 'Existing Unit',
        ]);

        $content = "type,name,specification,unit\n";
        $content .= "CONSUMABLE,Existing Item,Existing Spec,Existing Unit\n";

        $file = UploadedFile::fake()->createWithContent('items.csv', $content);

        $response = $this->actingAs($supervisor)->post(route('items.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Item already exists in database.');

        $importData = session('item_import_data');
        $this->assertFalse($importData[0]['is_valid']);
    }

    public function test_internal_file_duplicates_are_detected()
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $content = "type,name,specification,unit\n";
        $content .= "CONSUMABLE,Item X,Spec X,Unit X\n";
        $content .= "CONSUMABLE,Item X,Spec X,Unit X\n"; // Duplicate

        $file = UploadedFile::fake()->createWithContent('items.csv', $content);

        $response = $this->actingAs($supervisor)->post(route('items.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Duplicate item found in this file');

        $importData = session('item_import_data');
        $this->assertTrue($importData[0]['is_valid']);
        $this->assertFalse($importData[1]['is_valid']);
    }
}
