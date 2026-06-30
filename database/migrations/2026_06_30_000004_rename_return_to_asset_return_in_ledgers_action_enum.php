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
        // First expand the enum to include ASSET_RETURN
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'RETURN', 'ASSET_RETURN', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });

        // Update existing records (we only update action = RETURN to ASSET_RETURN. Note: REJECT is already REJECT, so it is untouched).
        DB::table('ledgers')->where('action', 'RETURN')->update(['action' => 'ASSET_RETURN']);

        // Remove RETURN from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'ASSET_RETURN', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Expand enum to include RETURN
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'RETURN', 'ASSET_RETURN', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });

        // Revert records
        DB::table('ledgers')->where('action', 'ASSET_RETURN')->update(['action' => 'RETURN']);

        // Remove ASSET_RETURN from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'RETURN', 'ALLOCATE',
                'DISPOSE', 'LOST', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });
    }
};
