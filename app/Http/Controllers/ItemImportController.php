<?php

namespace App\Http\Controllers;

use App\Exports\ItemTemplateExport;
use App\Imports\ItemImport;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ItemImportController extends Controller
{
    public function showUploadForm()
    {
        return view('supervisor.items.import.upload');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ItemTemplateExport, 'item_import_template.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $rows = Excel::toCollection(new ItemImport, $request->file('file'))->first();

        $previewData = [];
        $fileItems = []; // Track items in the current file for internal duplicate detection

        foreach ($rows as $index => $row) {
            // Map columns (assuming headings: type, name, specification, unit)
            $item = [
                'type' => strtoupper(trim($row['type'] ?? '')),
                'name' => trim($row['name'] ?? ''),
                'specification' => trim($row['specification'] ?? ''),
                'unit' => trim($row['unit'] ?? ''),
                'row_number' => $index + 2, // +1 for zero-index, +1 for heading row
            ];

            // Validation
            $validator = Validator::make($item, [
                'type' => 'required|in:CONSUMABLE,ASSET,RECOVERABLE',
                'name' => 'required|string|max:255',
                'specification' => 'nullable|string|max:255',
                'unit' => 'required|string|max:50',
            ]);

            $errors = $validator->errors()->all();

            $itemKey = strtolower($item['name'].'|'.($item['specification'] ?? '').'|'.$item['unit']);

            // Check uniqueness in DB
            $existsInDb = Item::where('name', $item['name'])
                ->where('specification', $item['specification'])
                ->where('unit', $item['unit'])
                ->exists();

            if ($existsInDb) {
                $errors[] = 'Item already exists in database.';
            }

            // Check uniqueness in file
            if (isset($fileItems[$itemKey])) {
                $errors[] = 'Duplicate item found in this file (see row '.$fileItems[$itemKey].').';
            } else {
                $fileItems[$itemKey] = $item['row_number'];
            }

            $item['errors'] = $errors;
            $item['is_valid'] = empty($errors);
            $previewData[] = $item;
        }

        // Store in session for final import
        session(['item_import_data' => $previewData]);

        return view('supervisor.items.import.preview', compact('previewData'));
    }

    public function store(Request $request)
    {
        $data = session('item_import_data');

        if (! $data) {
            return redirect()->route('items.import.form')->with('error', 'No data to import.');
        }

        $count = 0;
        foreach ($data as $row) {
            if ($row['is_valid']) {
                // Double check uniqueness before final save to be safe
                $exists = Item::where('name', $row['name'])
                    ->where('specification', $row['specification'])
                    ->where('unit', $row['unit'])
                    ->exists();

                if (! $exists) {
                    Item::create([
                        'type' => $row['type'],
                        'name' => $row['name'],
                        'specification' => $row['specification'],
                        'unit' => $row['unit'],
                    ]);
                    $count++;
                }
            }
        }

        session()->forget('item_import_data');

        return redirect()->route('items.index')->with('success', "Successfully imported {$count} items.");
    }
}
