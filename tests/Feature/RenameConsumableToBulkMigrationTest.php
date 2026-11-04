<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenameConsumableToBulkMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_up_and_down_works_properly()
    {
        // Assert the up migration transformed the default item type
        $bulk = DB::table('item_types')->where('name', 'Bulk')->first();
        $this->assertNotNull($bulk);
        $this->assertEquals('BULK', $bulk->base_behavior);

        $this->assertNull(DB::table('item_types')->where('name', 'Consumable')->first());

        // Test rolling back the rename_consumable_to_bulk migration
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_11_03_000000_rename_consumable_to_bulk_in_item_types.php',
        ])->assertSuccessful();

        // After rollback, it should be reverted to Consumable / CONSUMABLE
        $consumable = DB::table('item_types')->where('name', 'Consumable')->first();
        $this->assertNotNull($consumable);
        $this->assertEquals('CONSUMABLE', $consumable->base_behavior);

        // Re-run migration
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_11_03_000000_rename_consumable_to_bulk_in_item_types.php',
        ])->assertSuccessful();

        $bulkAgain = DB::table('item_types')->where('name', 'Bulk')->first();
        $this->assertNotNull($bulkAgain);
        $this->assertEquals('BULK', $bulkAgain->base_behavior);
    }
}
