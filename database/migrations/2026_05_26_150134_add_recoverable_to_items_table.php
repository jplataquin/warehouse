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
        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET', 'RECOVERABLE'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change RECOVERABLE items to ASSET before removing the type from enum
        DB::table('items')->where('type', 'RECOVERABLE')->update(['type' => 'ASSET']);

        Schema::table('items', function (Blueprint $table) {
            $table->enum('type', ['CONSUMABLE', 'ASSET'])->change();
        });
    }
};
