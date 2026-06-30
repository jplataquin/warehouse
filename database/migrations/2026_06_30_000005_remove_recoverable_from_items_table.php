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
        // 1. Update any existing RECOVERABLE items to CONSUMABLE
        DB::table('items')->where('type', 'RECOVERABLE')->update(['type' => 'CONSUMABLE']);

        // 2. Modify enum to only CONSUMABLE and ASSET
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Expand enum back to include RECOVERABLE
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET', 'RECOVERABLE'])->change();
        });
    }
};
