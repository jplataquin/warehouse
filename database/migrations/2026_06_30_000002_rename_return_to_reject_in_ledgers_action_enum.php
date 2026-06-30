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
        // First expand the enum to include REJECT
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });

        // Update existing records
        DB::table('ledgers')->where('action', 'RETURN')->update(['action' => 'REJECT']);

        // Remove RETURN from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
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
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'REJECT', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });

        // Revert records
        DB::table('ledgers')->where('action', 'REJECT')->update(['action' => 'RETURN']);

        // Remove REJECT from enum
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK', 'UTILIZE',
            ])->change();
        });
    }
};
