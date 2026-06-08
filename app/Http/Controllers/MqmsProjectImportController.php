<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\MqmsApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MqmsProjectImportController extends Controller
{
    protected $mqmsClient;

    public function __construct(MqmsApiClient $mqmsClient)
    {
        $this->mqmsClient = $mqmsClient;
    }

    public function preview()
    {
        $response = $this->mqmsClient->getProjects(['status' => 'ACTV']);
        
        // Handle potential errors from the API
        if (isset($response['error'])) {
            return redirect()->route('projects.index')->with('error', 'MQMS API Error: ' . $response['message']);
        }

        $mqmsProjects = $response['data'] ?? $response; // Adapt based on actual API response structure

        $previewData = [];
        foreach ($mqmsProjects as $mqmsProject) {
            $name = trim($mqmsProject['name'] ?? '');
            $mqmsId = $mqmsProject['id'] ?? null;

            if (!$mqmsId || !$name) continue;

            $errors = [];
            
            // Check if name exists (including soft-deleted)
            if (Project::withTrashed()->where('name', $name)->exists()) {
                $errors[] = "Project name already exists in database (check deleted projects).";
            }

            // Check if MQMS ID already mapped (including soft-deleted)
            if (Project::withTrashed()->where('mapped_to_project_id', $mqmsId)->exists()) {
                $errors[] = "Project is already imported/mapped (ID: $mqmsId).";
            }

            $previewData[] = [
                'id' => $mqmsId,
                'name' => $name,
                'errors' => $errors,
                'is_valid' => empty($errors),
            ];
        }

        return response()->view('supervisor.projects.import.preview', compact('previewData'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function store(Request $request)
    {
        $selectedProjectData = $request->input('selected_projects', []);

        if (empty($selectedProjectData)) {
            return redirect()->route('projects.index')->with('warning', 'No projects were selected for import.');
        }

        $count = 0;
        DB::transaction(function () use ($selectedProjectData, &$count) {
            foreach ($selectedProjectData as $data) {
                if (!isset($data['id'])) continue;

                // Find existing project including soft-deleted ones
                $project = Project::withTrashed()
                    ->where(function($q) use ($data) {
                        $q->where('name', $data['name'])
                          ->orWhere('mapped_to_project_id', $data['id']);
                    })->first();

                if (!$project) {
                    $project = Project::create([
                        'name' => $data['name'],
                        'mapped_to_project_id' => $data['id'],
                    ]);
                    $count++;
                } elseif ($project->trashed()) {
                    // Restore the project if it was soft-deleted
                    $project->restore();
                    // Update name/mapped_id just in case
                    $project->update([
                        'name' => $data['name'],
                        'mapped_to_project_id' => $data['id']
                    ]);
                    $count++;
                } else {
                    // Project already exists and is active, skip or update
                    continue;
                }

                if (isset($data['create_warehouse']) && $data['create_warehouse'] == '1') {
                    \App\Models\Warehouse::create([
                        'project_id' => $project->id,
                        'type' => 'SITE',
                        'name' => $project->name . ' - Site Warehouse',
                        'status' => 'ACTIVE'
                    ]);
                }
            }
        });

        return redirect()->route('projects.index')->with('success', "Successfully imported {$count} projects.");
    }
}
