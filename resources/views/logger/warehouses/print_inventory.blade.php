<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Inventory - {{ $warehouse->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
        }
        .print-header {
            border-bottom: 2px solid #333;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        .table thead th {
            background-color: #343a40 !important;
            color: white !important;
            padding: 6px 8px !important;
            -webkit-print-color-adjust: exact;
        }
        .table td {
            padding: 5px 8px !important;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container-fluid {
                padding: 10px !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid py-3">
        <div class="no-print mb-3 d-flex justify-content-between align-items-center">
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print Again</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close Window</button>
        </div>

        <div class="print-header">
            <div class="row align-items-center">
                <div class="col-8">
                    <h3 class="fw-bold mb-1">WAREHOUSE INVENTORY REPORT</h3>
                    <h5 class="text-secondary mb-1">{{ $warehouse->name }} ({{ $warehouse->type }})</h5>
                    <div class="small text-muted">
                        <strong>Project:</strong> {{ $warehouse->project ? $warehouse->project->name : 'N/A' }}
                    </div>
                </div>
                <div class="col-4 text-end">
                    <div class="small text-muted">Generated On:</div>
                    <div class="fw-bold">{{ now()->format('M d, Y h:i A') }}</div>
                    <div class="small text-muted mt-1">Total Active Items: {{ $items->count() }}</div>
                </div>
            </div>
        </div>

        @php
            $bulkItems = $items->where('type', 'BULK');
            $assetItems = $items->where('type', 'ASSET');
        @endphp

        <!-- Bulk Items Table -->
        <div class="mb-4">
            <h5 class="fw-bold mb-2 text-uppercase border-bottom pb-1">
                Bulk Items <span class="badge bg-secondary fs-6 align-middle">{{ $bulkItems->count() }}</span>
            </h5>
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">Item Name</th>
                        <th style="width: 30%;">Specification</th>
                        @if($warehouse->parent_id === null && $warehouse->children->isNotEmpty())
                            <th style="width: 25%;">Warehouse Location</th>
                        @endif
                        <th style="width: 25%;" class="text-end">Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bulkItems as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $item->name }}</td>
                            <td>{{ $item->specification ?? '—' }}</td>
                            @if($warehouse->parent_id === null && $warehouse->children->isNotEmpty())
                                <td>{{ $item->warehouse_context ? $item->warehouse_context->name : $warehouse->name }}</td>
                            @endif
                            <td class="text-end fw-bold">
                                {{ number_format($item->current_stock, 2) }} {{ $item->unit }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($warehouse->parent_id === null && $warehouse->children->isNotEmpty()) ? 5 : 4 }}" class="text-center py-3 text-muted">
                                No bulk items with inventory found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Asset Items Table -->
        <div class="mb-4">
            <h5 class="fw-bold mb-2 text-uppercase border-bottom pb-1">
                Asset Items <span class="badge bg-success fs-6 align-middle">{{ $assetItems->count() }}</span>
            </h5>
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">Item Name</th>
                        <th style="width: 30%;">Specification</th>
                        @if($warehouse->parent_id === null && $warehouse->children->isNotEmpty())
                            <th style="width: 25%;">Warehouse Location</th>
                        @endif
                        <th style="width: 25%;" class="text-end">Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assetItems as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $item->name }}</td>
                            <td>{{ $item->specification ?? '—' }}</td>
                            @if($warehouse->parent_id === null && $warehouse->children->isNotEmpty())
                                <td>{{ $item->warehouse_context ? $item->warehouse_context->name : $warehouse->name }}</td>
                            @endif
                            <td class="text-end fw-bold">
                                {{ number_format($item->current_stock, 2) }} {{ $item->unit }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($warehouse->parent_id === null && $warehouse->children->isNotEmpty()) ? 5 : 4 }}" class="text-center py-3 text-muted">
                                No asset items with inventory found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
