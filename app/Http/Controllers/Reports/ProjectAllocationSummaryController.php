<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Ledger;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectAllocationSummaryController extends Controller
{
    /**
     * Display the Project Allocation Summary report.
     */
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();

        $projectId = $request->input('project_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $reportData = collect();

        if ($projectId) {
            $reportData = Ledger::select('allocation_id', 'item_id')
                ->selectRaw('SUM(quantity) as total_quantity')
                ->where('action', 'ALLOCATE')
                ->where('project_id', $projectId)
                ->whereNotNull('allocation_id')
                ->when($fromDate, function ($query) use ($fromDate) {
                    return $query->whereDate('entry_date', '>=', $fromDate);
                })
                ->when($toDate, function ($query) use ($toDate) {
                    return $query->whereDate('entry_date', '<=', $toDate);
                })
                ->groupBy('allocation_id', 'item_id')
                ->with(['allocation', 'item'])
                ->get()
                ->groupBy('allocation_id')
                ->sortBy(function ($items) {
                    $allocation = $items->first()->allocation;
                    return $allocation ? $allocation->name : '';
                });
        }

        return view('reports.project-allocation-summary.index', [
            'projects' => $projects,
            'selectedProject' => $projectId ? Project::find($projectId) : null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'reportData' => $reportData,
        ]);
    }
}
