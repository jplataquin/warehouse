<?php

use App\Models\Ledger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Ledger::whereNull('project_id')->with('warehouse.project')->chunk(100, function ($ledgers) {
            foreach ($ledgers as $ledger) {
                if ($ledger->warehouse && $ledger->warehouse->project_id) {
                    $ledger->update(['project_id' => $ledger->warehouse->project_id]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to revert backfill
    }
};
