<?php

namespace App\Http\Controllers;

use App\Imports\ProjectImport;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Exports\ProjectTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProjectImportController extends Controller
{
    public function showUploadForm()
    {
        return view('supervisor.projects.import.upload');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProjectTemplateExport, 'project_import_template.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $collection = Excel::toCollection(new ProjectImport, $request->file('file'));
        $rows = $collection->first() ?? collect();
        
        $previewData = [];
        $fileProjects = [];

        foreach ($rows as $index => $row) {
            $project = [
                'name' => trim($row['name'] ?? ''),
                'create_warehouse' => strtoupper(trim($row['create_warehouse'] ?? 'NO')) === 'YES',
                'row_number' => $index + 2
            ];

            $validator = Validator::make($project, [
                'name' => 'required|string|max:255',
            ]);

            $errors = $validator->errors()->all();
            
            $nameKey = strtolower($project['name']);

            // Check uniqueness in DB
            if (Project::withTrashed()->where('name', $project['name'])->exists()) {
                $errors[] = "Project name already exists in database.";
            }

            // Check uniqueness in file
            if (isset($fileProjects[$nameKey])) {
                $errors[] = "Duplicate project name found in this file (see row " . $fileProjects[$nameKey] . ").";
            } else {
                $fileProjects[$nameKey] = $project['row_number'];
            }

            $project['errors'] = $errors;
            $project['is_valid'] = empty($errors);
            $previewData[] = $project;
        }

        session(['project_import_data' => $previewData]);

        return view('supervisor.projects.import.file_preview', compact('previewData'));
    }

    public function store(Request $request)
    {
        $data = session('project_import_data');

        if (!$data) {
            return redirect()->route('projects.import.form')->with('error', 'No data to import.');
        }

        $count = 0;
        DB::transaction(function () use ($data, &$count) {
            foreach ($data as $row) {
                if ($row['is_valid']) {
                    $exists = Project::withTrashed()->where('name', $row['name'])->exists();

                    if (!$exists) {
                        $project = Project::create([
                            'name' => $row['name'],
                        ]);
                        
                        if ($row['create_warehouse']) {
                            Warehouse::create([
                                'project_id' => $project->id,
                                'type' => 'SITE',
                                'name' => $project->name . ' - Site Warehouse',
                                'status' => 'ACTIVE'
                            ]);
                        }
                        
                        $count++;
                    }
                }
            }
        });

        session()->forget('project_import_data');

        return redirect()->route('projects.index')->with('success', "Successfully imported {$count} projects.");
    }
}
