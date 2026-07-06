<?php

namespace App\Http\Controllers;

use App\Exports\WarehouseTemplateExport;
use App\Imports\WarehouseImport;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseImportController extends Controller
{
    public function showUploadForm()
    {
        return view('supervisor.warehouses.import.upload');
    }

    public function downloadTemplate()
    {
        return Excel::download(new WarehouseTemplateExport, 'warehouse_import_template.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $collection = Excel::toCollection(new WarehouseImport, $request->file('file'));
        $rows = $collection->first() ?? collect();

        $previewData = [];
        $fileWarehouses = [];

        foreach ($rows as $index => $row) {
            $warehouse = [
                'name' => trim($row['name'] ?? ''),
                'type' => strtoupper(trim($row['type'] ?? '')),
                'status' => 'ACTIVE',
                'project_id' => null,
                'row_number' => $index + 2,
            ];

            $validator = Validator::make($warehouse, [
                'name' => 'required|string|max:255',
                'type' => 'required|in:SITE,CENTRAL,EQUIPMENT/VEHICLE,OFFICE/FACILITY',
            ]);

            $errors = $validator->errors()->all();

            // Check uniqueness in DB (name and project_id which is null here)
            $existsInDb = Warehouse::where('name', $warehouse['name'])
                ->whereNull('project_id')
                ->exists();

            if ($existsInDb) {
                $errors[] = 'Warehouse name already exists.';
            }

            // Check uniqueness in file
            if (isset($fileWarehouses[$warehouse['name']])) {
                $errors[] = 'Duplicate warehouse name found in this file (see row '.$fileWarehouses[$warehouse['name']].').';
            } else {
                $fileWarehouses[$warehouse['name']] = $warehouse['row_number'];
            }

            $warehouse['errors'] = $errors;
            $warehouse['is_valid'] = empty($errors);
            $previewData[] = $warehouse;
        }

        session(['warehouse_import_data' => $previewData]);

        return view('supervisor.warehouses.import.preview', compact('previewData'));
    }

    public function store(Request $request)
    {
        $data = session('warehouse_import_data');

        if (! $data) {
            return redirect()->route('warehouses.import.form')->with('error', 'No data to import.');
        }

        $count = 0;
        DB::transaction(function () use ($data, &$count) {
            foreach ($data as $row) {
                if ($row['is_valid']) {
                    // Double check uniqueness
                    $exists = Warehouse::where('name', $row['name'])
                        ->where('project_id', $row['project_id'])
                        ->exists();

                    if (! $exists) {
                        Warehouse::create([
                            'name' => $row['name'],
                            'type' => $row['type'],
                            'project_id' => $row['project_id'],
                            'status' => $row['status'],
                        ]);
                        $count++;
                    }
                }
            }
        });

        session()->forget('warehouse_import_data');

        return redirect()->route('warehouses.index')->with('success', "Successfully imported {$count} warehouses.");
    }
}
