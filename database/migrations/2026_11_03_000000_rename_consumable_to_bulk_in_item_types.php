<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First expand the enum to include BULK
        Schema::table('item_types', function (Blueprint $table) {
            $table->enum('base_behavior', ['CONSUMABLE', 'ASSET', 'BULK'])->change();
        });

        // Update existing records
        DB::table('item_types')->where('base_behavior', 'CONSUMABLE')->update(['base_behavior' => 'BULK']);
        DB::table('item_types')->where('name', 'Consumable')->update(['name' => 'Bulk']);

        // Shrink enum to only BULK and ASSET
        Schema::table('item_types', function (Blueprint $table) {
            $table->enum('base_behavior', ['BULK', 'ASSET'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Expand enum to include CONSUMABLE
        Schema::table('item_types', function (Blueprint $table) {
            $table->enum('base_behavior', ['CONSUMABLE', 'ASSET', 'BULK'])->change();
        });

        // Revert records
        DB::table('item_types')->where('base_behavior', 'BULK')->update(['base_behavior' => 'CONSUMABLE']);
        DB::table('item_types')->where('name', 'Bulk')->update(['name' => 'Consumable']);

        // Shrink enum to only CONSUMABLE and ASSET
        Schema::table('item_types', function (Blueprint $table) {
            $table->enum('base_behavior', ['CONSUMABLE', 'ASSET'])->change();
        });
    }
};
