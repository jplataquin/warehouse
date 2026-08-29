<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Warehouse;
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
        if ($warehouse->type !== 'SITE' || ! $warehouse->project || ! $warehouse->project->mapped_to_project_id) {
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
            'status' => 'APRV',
        ]);

        if (isset($response['error'])) {
            return redirect()->route('warehouses.show', $warehouse)->with('error', 'MQMS API Error: '.$response['message']);
        }

        $mqmsComponents = $response['data'] ?? $response;

        $previewData = [];
        $existingAllocations = Allocation::where('warehouse_id', $warehouse->id)->get();
        $existingAllocationsByMappedId = $existingAllocations->whereNotNull('mapped_to_component_id')
            ->keyBy('mapped_to_component_id');

        foreach ($mqmsComponents as $component) {
            $name = trim($component['name'] ?? $component['description'] ?? '');
            $mqmsId = $component['id'] ?? null;

            if (! $mqmsId || ! $name) {
                continue;
            }

            $errors = [];
            $warning = null;
            $isUpdate = false;

            // Check if MQMS ID already mapped in this warehouse
            $existingAllocation = $existingAllocationsByMappedId->get($mqmsId);

            if ($existingAllocation) {
                // If it exists, check if name has changed
                if ($existingAllocation->name !== $name) {
                    // Check if the NEW name is already taken by some OTHER allocation in the same warehouse
                    $nameExists = $existingAllocations->where('name', $name)
                        ->where('id', '!=', $existingAllocation->id)
                        ->isNotEmpty();
                    if ($nameExists) {
                        $errors[] = "Name has changed from '{$existingAllocation->name}' to '{$name}', but another allocation with this name already exists in this warehouse.";
                    } else {
                        $isUpdate = true;
                        $warning = "Component name has changed in MQMS. Importing will update it from '{$existingAllocation->name}' to '{$name}'.";
                    }
                } else {
                    $errors[] = 'Component is already imported to this warehouse.';
                }
            } else {
                // If MQMS ID doesn't exist, check if name exists in this warehouse
                $nameExists = $existingAllocations->where('name', $name)->isNotEmpty();
                if ($nameExists) {
                    $errors[] = 'An allocation with this name already exists in this warehouse.';
                }
            }

            $previewData[] = [
                'id' => $mqmsId,
                'name' => $name,
                'errors' => $errors,
                'warning' => $warning,
                'is_update' => $isUpdate,
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

        $countCreated = 0;
        $countUpdated = 0;

        DB::transaction(function () use ($selectedComponents, $warehouse, &$countCreated, &$countUpdated) {
            foreach ($selectedComponents as $data) {
                if (! isset($data['id'])) {
                    continue;
                }

                $mqmsId = $data['id'];
                $name = $data['name'];

                // Retrieve existing allocation with this MQMS ID
                $existing = Allocation::where('warehouse_id', $warehouse->id)
                    ->where('mapped_to_component_id', $mqmsId)
                    ->first();

                if ($existing) {
                    if ($existing->name !== $name) {
                        // Double check name collision to prevent updates to duplicate names
                        $collision = Allocation::where('warehouse_id', $warehouse->id)
                            ->where('name', $name)
                            ->where('id', '!=', $existing->id)
                            ->exists();

                        if (! $collision) {
                            $existing->update(['name' => $name]);
                            $countUpdated++;
                        }
                    }
                } else {
                    // Double check name collision for new imports
                    $collision = Allocation::where('warehouse_id', $warehouse->id)
                        ->where('name', $name)
                        ->exists();

                    if (! $collision) {
                        Allocation::create([
                            'warehouse_id' => $warehouse->id,
                            'name' => $name,
                            'mapped_to_component_id' => $mqmsId,
                        ]);
                        $countCreated++;
                    }
                }
            }
        });

        $message = "Successfully processed MQMS components.";
        if ($countCreated > 0 && $countUpdated > 0) {
            $message = "Successfully imported {$countCreated} new components and updated {$countUpdated} existing component names.";
        } elseif ($countCreated > 0) {
            $message = "Successfully imported {$countCreated} new components as allocations.";
        } elseif ($countUpdated > 0) {
            $message = "Successfully updated {$countUpdated} existing component names.";
        } else {
            return redirect()->route('warehouses.show', $warehouse)->with('warning', 'No components were imported or updated.');
        }

        return redirect()->route('warehouses.show', $warehouse)->with('success', $message);
    }
}
