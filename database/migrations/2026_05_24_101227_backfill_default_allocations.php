<?php

use App\Models\Project;
use App\Models\Allocation;
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
        Project::all()->each(function (Project $project) {
            $project->allocations()->firstOrCreate([
                'name' => 'No Allocation',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Allocation::where('name', 'No Allocation')->delete();
    }
};
