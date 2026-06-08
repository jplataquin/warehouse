<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Supervisor User
        \App\Models\User::create([
            'name' => 'Supervisor User',
            'email' => 'supervisor@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
        ]);

        // Logger User
        \App\Models\User::create([
            'name' => 'Logger User',
            'email' => 'logger@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'logger',
        ]);

        // Projects
        $projectA = \App\Models\Project::create(['name' => 'Project Alpha']);
        $projectB = \App\Models\Project::create(['name' => 'Project Beta']);

        // Warehouses
        $central = \App\Models\Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main Central Warehouse',
            'status' => 'ACTIVE'
        ]);

        $siteA = \App\Models\Warehouse::create([
            'project_id' => $projectA->id,
            'type' => 'SITE',
            'name' => 'Alpha Site Warehouse',
            'status' => 'ACTIVE'
        ]);

        // Items
        \App\Models\Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement',
            'specification' => '40kg Bag',
            'unit' => 'Bags'
        ]);

        \App\Models\Item::create([
            'type' => 'ASSET',
            'name' => 'Drill Machine',
            'specification' => 'Bosch GSB 13 RE',
            'unit' => 'Units'
        ]);
    }
}
