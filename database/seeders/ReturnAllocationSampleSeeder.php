<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ReturnAllocationSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure an Admin user exists for audit tracking
        $admin = User::firstOrCreate(
            ['email' => 'admin@warehouse.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // 2. Create sample Project
        $project = Project::firstOrCreate(
            ['name' => 'Skyline Tower Project']
        );

        // 3. Create sample Warehouse tied to the project
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'Skyline Site Warehouse'],
            [
                'project_id' => $project->id,
                'type' => 'SITE',
                'status' => 'ACTIVE',
            ]
        );

        // 4. Create sample Allocation Target
        $allocation = Allocation::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'name' => 'Foundation & Columns',
            ]
        );

        // 5. Create sample BULK Items
        $cement = Item::firstOrCreate(
            ['name' => 'Portland Cement (Type 1)'],
            [
                'type' => 'BULK',
                'specification' => '40kg Bag',
                'unit' => 'Bags',
                'status' => 'Operational',
            ]
        );

        $rebar = Item::firstOrCreate(
            ['name' => 'Deformed Steel Bar 16mm'],
            [
                'type' => 'BULK',
                'specification' => 'Grade 40, 6 meters',
                'unit' => 'Pcs',
                'status' => 'Operational',
            ]
        );

        // 6. Seed Inbound Delivery (Stock arrival into the warehouse)
        Ledger::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'item_id' => $cement->id,
                'action' => 'DELIVERY',
                'po_number' => 'PO-CEMENT-001',
            ],
            [
                'entry_date' => now()->subDays(5)->format('Y-m-d'),
                'type' => 'IN',
                'quantity' => 500,
                'status' => 'APPROVED',
                'delivery_receipt' => 'DR-CEMENT-001',
                'plate_no' => 'WHS-8801',
                'project_id' => $project->id,
                'remarks' => 'Inbound initial supply delivery of cement',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        Ledger::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'item_id' => $rebar->id,
                'action' => 'DELIVERY',
                'po_number' => 'PO-REBAR-001',
            ],
            [
                'entry_date' => now()->subDays(5)->format('Y-m-d'),
                'type' => 'IN',
                'quantity' => 200,
                'status' => 'APPROVED',
                'delivery_receipt' => 'DR-REBAR-001',
                'plate_no' => 'WHS-8802',
                'project_id' => $project->id,
                'remarks' => 'Inbound initial supply delivery of rebar',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // 7. Seed Outbound Allocation (OUT - ALLOCATE to the project and allocation target)
        // 100 bags of cement allocated (400 remaining in warehouse, 100 available to return)
        Ledger::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'item_id' => $cement->id,
                'action' => 'ALLOCATE',
                'allocation_id' => $allocation->id,
                'remarks' => 'Allocated for ground-level column pouring',
            ],
            [
                'entry_date' => now()->subDays(2)->format('Y-m-d'),
                'type' => 'OUT',
                'quantity' => 100,
                'status' => 'APPROVED',
                'project_id' => $project->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // 60 pcs of rebar allocated (140 remaining in warehouse, 60 available to return)
        Ledger::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'item_id' => $rebar->id,
                'action' => 'ALLOCATE',
                'allocation_id' => $allocation->id,
                'remarks' => 'Allocated for column steel footing fabrication',
            ],
            [
                'entry_date' => now()->subDays(2)->format('Y-m-d'),
                'type' => 'OUT',
                'quantity' => 60,
                'status' => 'APPROVED',
                'project_id' => $project->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $this->command->info('------------------------------------------------------------');
        $this->command->info('RETURN_ALLOCATION Sample Data Seeded Successfully!');
        $this->command->info('------------------------------------------------------------');
        $this->command->info("Project:    {$project->name} (ID: {$project->id})");
        $this->command->info("Warehouse:  {$warehouse->name} (ID: {$warehouse->id})");
        $this->command->info("Allocation: {$allocation->name} (ID: {$allocation->id})");
        $this->command->info('');
        $this->command->info("Items & Allocated Balances Available for Return:");
        $this->command->info("  1. Portland Cement (Type 1)");
        $this->command->info("     - Warehouse Current Stock: 400 Bags");
        $this->command->info("     - OUT - ALLOCATE Quantity: 100 Bags (Max returnable: 100)");
        $this->command->info("  2. Deformed Steel Bar 16mm");
        $this->command->info("     - Warehouse Current Stock: 140 Pcs");
        $this->command->info("     - OUT - ALLOCATE Quantity: 60 Pcs (Max returnable: 60)");
        $this->command->info('------------------------------------------------------------');
    }
}
