<?php

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
        Schema::table('asset_utilizations', function (Blueprint $table) {
            $table->dropColumn(['utilized_by', 'utilized_at', 'returned_at', 'remarks']);
            $table->foreignId('ledger_id')->after('item_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_utilizations', function (Blueprint $table) {
            $table->dropForeign(['ledger_id']);
            $table->dropColumn('ledger_id');
            $table->string('utilized_by')->nullable();
            $table->timestamp('utilized_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('remarks')->nullable();
        });
    }
};
