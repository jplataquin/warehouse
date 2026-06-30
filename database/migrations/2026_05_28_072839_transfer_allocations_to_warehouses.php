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
        // Delete existing allocations to avoid foreign key issues and start fresh as per user choice
        DB::table('allocations')->delete();

        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->foreignId('warehouse_id')->after('id')->constrained()->onDelete('cascade');
        });

        // Backfill "No Allocation" for all existing warehouses
        $warehouses = DB::table('warehouses')->get();
        foreach ($warehouses as $warehouse) {
            DB::table('allocations')->insert([
                'warehouse_id' => $warehouse->id,
                'name' => 'No Allocation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
            $table->foreignId('project_id')->after('id')->constrained()->onDelete('cascade');
        });
    }
};
