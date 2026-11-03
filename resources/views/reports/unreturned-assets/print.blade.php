<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unreturned Assets Report - {{ $warehouseName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 12px; cursor: pointer;">Print Report</button>
    </div>

    <div class="header">
        <h1>Unreturned Assets Report</h1>
        <p><strong>Filter:</strong> {{ $warehouseName }}</p>
        <p><strong>Generated On:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset Name</th>
                <th>Specification</th>
                <th>Asset Type</th>
                <th>Origin Warehouse</th>
                <th>Action</th>
                <th>Assigned To</th>
                <th>Plate No</th>
                <th>Out Date</th>
                <th class="text-center">Days Out</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->specification ?? '-' }}</td>
                    <td>{{ $item->item_type }}</td>
                    <td>{{ $item->warehouse }}</td>
                    <td>{{ $item->action }}</td>
                    <td>{{ $item->assigned_to }}</td>
                    <td>{{ $item->plate_no }}</td>
                    <td>{{ $item->out_date }}</td>
                    <td class="text-center"><strong>{{ $item->days_out }}</strong></td>
                    <td>{{ $item->remarks }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No unreturned assets found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
