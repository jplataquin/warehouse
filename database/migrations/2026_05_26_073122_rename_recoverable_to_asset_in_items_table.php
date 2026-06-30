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
        // 1. Expand enum to include ASSET
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'RECOVERABLE', 'ASSET'])->change();
        });

        // 2. Update existing records
        DB::table('items')->where('type', 'RECOVERABLE')->update(['type' => 'ASSET']);

        // 3. Remove RECOVERABLE from enum
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'RECOVERABLE', 'ASSET'])->change();
        });

        DB::table('items')->where('type', 'ASSET')->update(['type' => 'RECOVERABLE']);

        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'RECOVERABLE'])->change();
        });
    }
};
