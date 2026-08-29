<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Allocation Summary - {{ $project->name }}</title>
    <!-- Use Bootstrap CSS for basic styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #000;
        }
        .print-header {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 8px;
        }
        .print-header h2 { font-size: 22px; }
        .print-header h4 { font-size: 16px; }
        
        .project-info {
            background-color: #fff;
            border: 1px solid #000;
            padding: 12px;
            margin-bottom: 20px;
        }
        
        .table {
            font-size: 10px;
            border-color: #000 !important;
        }
        .table thead th {
            background-color: #000 !important;
            color: #fff !important;
            padding: 6px 8px !important;
            -webkit-print-color-adjust: exact;
            border-bottom: 1px solid #000 !important;
        }
        .table td {
            padding: 5px 8px !important;
            border-color: #000 !important;
        }
        .allocation-group-header {
            background-color: #f2f2f2 !important;
            font-weight: bold;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close Window</button>
            <button onclick="window.print()" class="btn btn-dark btn-sm">Print Report</button>
        </div>

        <div class="print-header d-flex justify-content-between align-items-end">
            <div>
                <h2 class="fw-bold mb-0 text-uppercase">Project Allocation Summary</h2>
                <div class="text-muted small text-uppercase">Generated on {{ now()->format('M d, Y H:i') }}</div>
            </div>
            <div class="text-end">
                <h4 class="mb-0 fw-bold">{{ config('app.name', 'WAREHOUSE INFOSYS') }}</h4>
                <div class="small">System Reports</div>
            </div>
        </div>

        <div class="project-info shadow-sm">
            <div class="row g-3">
                <div class="col-6">
                    <div class="small text-muted text-uppercase fw-bold">Project Name</div>
                    <div class="h5 fw-bold mb-0 text-dark">{{ $project->name }}</div>
                </div>
                <div class="col-6 text-end">
                    <div class="small text-muted text-uppercase fw-bold">Reporting Period</div>
                    <div class="h5 fw-bold mb-0 text-dark">
                        {{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('M d, Y') : 'Beginning' }}
                        to
                        {{ $toDate ? \Carbon\Carbon::parse($toDate)->format('M d, Y') : 'Present' }}
                    </div>
                </div>
            </div>
        </div>

        @if($reportData->isEmpty())
            <div class="alert alert-secondary text-center py-4 border">
                No allocation data found for this project and date range.
            </div>
        @else
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Allocation Target / Item Name</th>
                        <th class="text-end pe-3" style="width: 250px;">Total Quantity Allocated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $allocationId => $items)
                        @php
                            $allocationName = $items->first()->allocation ? $items->first()->allocation->name : 'Unspecified Target';
                        @endphp
                        <tr class="allocation-group-header">
                            <td colspan="2" class="ps-3 py-2 text-dark">
                                <strong>Target: {{ $allocationName }}</strong>
                            </td>
                        </tr>
                        @foreach($items as $data)
                            <tr>
                                <td class="ps-4 py-2 text-dark">
                                    {{ $data->item ? $data->item->name : 'Unspecified Item' }}
                                    @if($data->item && $data->item->specification)
                                        <span class="text-muted small">({{ $data->item->specification }})</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 py-2 fw-bold text-dark">
                                    {{ number_format($data->total_quantity, 2) }}
                                    <small class="text-muted fw-normal ms-1">{{ $data->item ? $data->item->unit : '' }}</small>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>