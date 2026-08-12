<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable item_type_id column first
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_type_id')->nullable()->after('type');
            $table->foreign('item_type_id')->references('id')->on('item_types')->onDelete('restrict');
        });

        // 2. Insert default item types if they do not exist
        $consumableId = DB::table('item_types')->insertGetId([
            'name' => 'Consumable',
            'base_behavior' => 'CONSUMABLE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assetId = DB::table('item_types')->insertGetId([
            'name' => 'Asset',
            'base_behavior' => 'ASSET',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Map existing items to the new item_type_id
        DB::table('items')->where('type', 'CONSUMABLE')->update(['item_type_id' => $consumableId]);
        DB::table('items')->where('type', 'ASSET')->update(['item_type_id' => $assetId]);

        // 4. Drop the type column and make item_type_id required
        // Note: For SQLite, changing columns or dropping columns must be handled carefully.
        // Laravel 11's schema builder supports dropping columns and changing them in SQLite natively.
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_type_id')->nullable(false)->change();
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET'])->nullable()->after('item_type_id');
        });

        // Map item_type_id back to type enum
        $itemTypes = DB::table('item_types')->get()->keyBy('id');
        foreach ($itemTypes as $id => $itemType) {
            DB::table('items')
                ->where('item_type_id', $id)
                ->update(['type' => $itemType->base_behavior]);
        }

        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET'])->nullable(false)->change();
            $table->dropForeign(['item_type_id']);
            $table->dropColumn('item_type_id');
        });

        DB::table('item_types')->whereIn('name', ['Consumable', 'Asset'])->delete();
    }
};
