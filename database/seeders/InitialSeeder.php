<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Supervisor User
        User::create([
            'name' => 'Supervisor User',
            'email' => 'supervisor@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
        ]);

        // Logger User
        User::create([
            'name' => 'Logger User',
            'email' => 'logger@warehouse.com',
            'password' => bcrypt('password'),
            'role' => 'logger',
        ]);

        // Projects
        $projectA = Project::create(['name' => 'Project Alpha']);
        $projectB = Project::create(['name' => 'Project Beta']);

        // Warehouses
        $central = Warehouse::create([
            'type' => 'CENTRAL',
            'name' => 'Main Central Warehouse',
            'status' => 'ACTIVE',
        ]);

        $siteA = Warehouse::create([
            'project_id' => $projectA->id,
            'type' => 'SITE',
            'name' => 'Alpha Site Warehouse',
            'status' => 'ACTIVE',
        ]);

        // Items
        Item::create([
            'type' => 'CONSUMABLE',
            'name' => 'Cement',
            'specification' => '40kg Bag',
            'unit' => 'Bags',
        ]);

        Item::create([
            'type' => 'ASSET',
            'name' => 'Drill Machine',
            'specification' => 'Bosch GSB 13 RE',
            'unit' => 'Units',
        ]);
    }
}
