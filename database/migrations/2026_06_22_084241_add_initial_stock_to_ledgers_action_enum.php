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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'MAINTENANCE', 'CORRECTION',
                'INITIAL_STOCK',
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete INITIAL_STOCK ledger entries to avoid MySQL errors when shrinking the ENUM
        DB::table('ledgers')->where('action', 'INITIAL_STOCK')->delete();

        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('action', [
                'TRANSFER', 'DELIVERY', 'DIRECT', 'ALLOCATE',
                'DISPOSE', 'LOST', 'RETURN', 'MAINTENANCE', 'CORRECTION',
            ])->change();
        });
    }
};
