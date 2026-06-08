<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class AssetItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            ['name' => 'Generator Set', 'specification' => '50kVA Silent Type', 'unit' => 'Unit'],
            ['name' => 'Excavator', 'specification' => 'Medium Size 20-ton', 'unit' => 'Unit'],
            ['name' => 'Concrete Mixer', 'specification' => 'One-bagger Heavy Duty', 'unit' => 'Unit'],
            ['name' => 'Welding Machine', 'specification' => '400A Inverter Type', 'unit' => 'Unit'],
            ['name' => 'Air Compressor', 'specification' => '5HP Twin Tank', 'unit' => 'Unit'],
            ['name' => 'Jackhammer', 'specification' => 'Pneumatic Heavy Duty', 'unit' => 'Unit'],
            ['name' => 'Surveying Level', 'specification' => 'Automatic Level with Tripod', 'unit' => 'Set'],
            ['name' => 'Plate Compactor', 'specification' => '6.5HP Gasoline Engine', 'unit' => 'Unit'],
            ['name' => 'Electric Drill', 'specification' => 'Industrial Grade Hammer Drill', 'unit' => 'Unit'],
            ['name' => 'Chain Saw', 'specification' => '24-inch Guide Bar Gasoline', 'unit' => 'Unit'],
        ];

        foreach ($assets as $asset) {
            Item::updateOrCreate(
                ['name' => $asset['name'], 'specification' => $asset['specification']],
                [
                    'type' => 'ASSET',
                    'unit' => $asset['unit']
                ]
            );
        }
    }
}
