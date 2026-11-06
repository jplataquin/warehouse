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
        // First expand the enum to include RETURN_ASSET
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'ASSET_RETURN', 'RETURN_ASSET', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE', 'RETURN_ALLOCATION',
            ])->change();
        });

        // Update existing records
        DB::table('ledgers')->where('action', 'ASSET_RETURN')->update(['action' => 'RETURN_ASSET']);

        // Remove ASSET_RETURN from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'RETURN_ASSET', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE', 'RETURN_ALLOCATION',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Expand enum to include ASSET_RETURN
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'ASSET_RETURN', 'RETURN_ASSET', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE', 'RETURN_ALLOCATION',
            ])->change();
        });

        // Revert records
        DB::table('ledgers')->where('action', 'RETURN_ASSET')->update(['action' => 'ASSET_RETURN']);

        // Remove RETURN_ASSET from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'ASSET_RETURN', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE', 'RETURN_ALLOCATION',
            ])->change();
        });
    }
};
