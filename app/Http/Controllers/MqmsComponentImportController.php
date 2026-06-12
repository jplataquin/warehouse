<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Allocation;
use App\Services\MqmsApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MqmsComponentImportController extends Controller
{
    protected $mqmsClient;

    public function __construct(MqmsApiClient $mqmsClient)
    {
        $this->mqmsClient = $mqmsClient;
    }

    public function sections(Warehouse $warehouse)
    {
        if ($warehouse->type !== 'SITE' || !$warehouse->project || !$warehouse->project->mapped_to_project_id) {
            return response()->json(['error' => 'Warehouse is not mapped to an MQMS project.'], 422);
        }

        $response = $this->mqmsClient->getSections(['project_id' => $warehouse->project->mapped_to_project_id]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['message']], 500);
        }

        return response()->json($response['data'] ?? $response);
    }

    public function preview(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'section_id' => 'required',
        ]);

        $response = $this->mqmsClient->getComponents([
            'section_id' => $request->section_id,
            'status' => 'APRV'
        ]);

        if (isset($response['error'])) {
            return redirect()->route('warehouses.show', $warehouse)->with('error', 'MQMS API Error: ' . $response['message']);
        }

        $mqmsComponents = $response['data'] ?? $response;

        $previewData = [];
        $existingMappedIds = Allocation::where('warehouse_id', $warehouse->id)
            ->whereNotNull('mapped_to_component_id')
            ->pluck('mapped_to_component_id')
            ->toArray();

        foreach ($mqmsComponents as $component) {
            $name = trim($component['name'] ?? $component['description'] ?? '');
            $mqmsId = $component['id'] ?? null;

            if (!$mqmsId || !$name) continue;

            $errors = [];
            
            // Check if MQMS ID already mapped in this warehouse
            if (in_array($mqmsId, $existingMappedIds)) {
                $errors[] = "Component is already imported to this warehouse.";
            }

            // Check if name exists in this warehouse
            if (Allocation::where('warehouse_id', $warehouse->id)->where('name', $name)->exists()) {
                $errors[] = "An allocation with this name already exists in this warehouse.";
            }

            $previewData[] = [
                'id' => $mqmsId,
                'name' => $name,
                'errors' => $errors,
                'is_valid' => empty($errors),
            ];
        }

        return view('supervisor.warehouses.import-components.preview', compact('previewData', 'warehouse'));
    }

    public function store(Request $request, Warehouse $warehouse)
    {
        $selectedComponents = $request->input('selected_components', []);

        // Filter out entries that were not selected (they will not have the 'id' key set because the checkbox was unchecked)
        $selectedComponents = array_filter($selectedComponents, function ($data) {
            return isset($data['id']);
        });

        if (empty($selectedComponents)) {
            return redirect()->route('warehouses.show', $warehouse)->with('warning', 'No components were selected for import.');
        }

        $count = 0;
        DB::transaction(function () use ($selectedComponents, $warehouse, &$count) {
            foreach ($selectedComponents as $data) {
                if (!isset($data['id'])) continue;

                // Uniqueness check
                $exists = Allocation::where('warehouse_id', $warehouse->id)
                    ->where(function($query) use ($data) {
                        $query->where('name', $data['name'])
                              ->orWhere('mapped_to_component_id', $data['id']);
                    })
                    ->exists();

                if (!$exists) {
                    Allocation::create([
                        'warehouse_id' => $warehouse->id,
                        'name' => $data['name'],
                        'mapped_to_component_id' => $data['id'],
                    ]);
                    $count++;
                }
            }
        });

        return redirect()->route('warehouses.show', $warehouse)->with('success', "Successfully imported {$count} components as allocations.");
    }
}
