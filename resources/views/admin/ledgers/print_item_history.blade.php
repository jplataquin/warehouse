<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Ledger - {{ $item->name }} - {{ $warehouse->name }}</title>
    <!-- Use Bootstrap CSS for basic styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px; /* Reduced from 12px */
        }
        .print-header {
            border-bottom: 2px solid #333;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        .print-header h2 { font-size: 20px; }
        .print-header h4 { font-size: 16px; }
        
        .item-info {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .item-info h4 { font-size: 16px; }
        .item-info h3 { font-size: 18px; }
        
        .table {
            font-size: 9px; /* Even smaller font for table data */
        }
        .table thead th {
            background-color: #343a40 !important;
            color: white !important;
            padding: 4px 6px !important;
            -webkit-print-color-adjust: exact;
        }
        .table td {
            padding: 3px 6px !important; /* Compact padding */
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
        .badge-in { background-color: #198754 !important; color: white !important; }
        .badge-out { background-color: #dc3545 !important; color: white !important; }
        .badge {
            font-size: 8px;
            padding: 2px 6px;
            border-radius: 8px;
            display: inline-block;
            -webkit-print-color-adjust: exact;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close Window</button>
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print Ledger</button>
        </div>

        <div class="print-header d-flex justify-content-between align-items-end">
            <div>
                <h2 class="fw-bold mb-0">ITEM LEDGER</h2>
                <div class="text-muted small text-uppercase">Generated on {{ now()->format('M d, Y H:i') }}</div>
            </div>
            <div class="text-end">
                <h4 class="mb-0 text-primary">{{ config('app.name', 'WAREHOUSE INFOSYS') }}</h4>
                <div class="small">{{ $warehouse->name }}</div>
            </div>
        </div>

        <div class="row item-info g-0 shadow-sm border">
            <div class="col-md-6 border-end pe-3">
                <div class="small text-muted text-uppercase fw-bold">Item Details</div>
                <div class="h4 fw-bold mb-1">{{ $item->name }}</div>
                <div class="text-muted">{{ $item->specification }}</div>
                <div class="mt-2"><span class="badge bg-secondary text-white">{{ $item->type }}</span></div>
            </div>
            <div class="col-md-3 border-end px-3 d-flex flex-column justify-content-center">
                <div class="small text-muted text-uppercase fw-bold">Unit</div>
                <div class="h5 mb-0">{{ $item->unit }}</div>
            </div>
            <div class="col-md-3 ps-3 d-flex flex-column justify-content-center text-end">
                <div class="small text-muted text-uppercase fw-bold">Current Balance</div>
                <div class="h3 fw-bold text-primary mb-0">{{ $balance }}</div>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 6%;">Type</th>
                    <th style="width: 10%;">Action</th>
                    <th style="width: 15%;">Allocation</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 8%;">Run. Bal.</th>
                    <th style="width: 21%;">Reference Nos.</th>
                    <th style="width: 22%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBal = $openingBalance; @endphp
                @forelse($ledgers as $ledger)
                @php
                    if ($ledger->type === 'IN') {
                        $runningBal += $ledger->quantity;
                    } else {
                        $runningBal -= $ledger->quantity;
                    }
                @endphp
                <tr>
                    <td>
                        <div class="fw-bold">{{ $ledger->entry_date ? $ledger->entry_date->format('Y-m-d') : 'N/A' }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $ledger->type === 'IN' ? 'badge-in' : 'badge-out' }}">
                            {{ $ledger->type }}
                        </span>
                    </td>
                    <td><span class="small fw-bold">{{ $ledger->action }}</span></td>
                    <td class="small">{{ $ledger->allocation->name ?? 'N/A' }}</td>
                    <td class="fw-bold text-end">{{ number_format($ledger->quantity, 2) }}</td>
                    <td class="fw-bold text-end text-primary">{{ number_format($runningBal, 2) }}</td>
                    <td>
                        @if($ledger->po_number) <div class="small">PO: {{ $ledger->po_number }}</div> @endif
                        @if($ledger->delivery_receipt) <div class="small">DR: {{ $ledger->delivery_receipt }}</div> @endif
                        @if($ledger->offical_receipt) <div class="small">OR: {{ $ledger->offical_receipt }}</div> @endif
                    </td>
                    <td class="small">{{ $ledger->remarks ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4">No records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4 text-center text-muted small">
            *** End of Report ***
        </div>
    </div>
</body>
</html>
