@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.project-allocation-summary', ['project_id' => $project->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}">Project Allocation Summary</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ledger Source Entries</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800">Allocation Source Entries</h1>
                    <p class="text-muted mb-0">Detailed ledger entries contributing to the sum total.</p>
                </div>
                <a href="{{ route('reports.project-allocation-summary', ['project_id' => $project->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Report
                </a>
            </div>

            <!-- Context Info Card -->
            <div class="card shadow-sm border-0 mb-4 bg-light">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small text-uppercase fw-bold d-block">Project</span>
                            <span class="fw-bold text-dark fs-5">{{ $project->name }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small text-uppercase fw-bold d-block">Allocation Target</span>
                            <span class="fw-bold text-dark fs-5">
                                <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ $allocation->name }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small text-uppercase fw-bold d-block">Item</span>
                            <span class="fw-bold text-dark fs-5">
                                <i class="bi bi-box-seam text-secondary me-1"></i>{{ $item->name }}
                                @if($item->specification)
                                    <span class="text-muted small fw-normal">({{ $item->specification }})</span>
                                @endif
                            </span>
                        </div>
                        @if($fromDate || $toDate)
                        <div class="col-12 border-top pt-3 mt-3">
                            <span class="text-muted small text-uppercase fw-bold d-inline-block me-2">Filtered Date Range:</span>
                            <span class="badge bg-secondary py-2 px-3">
                                {{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('M d, Y') : 'Beginning' }}
                                to
                                {{ $toDate ? \Carbon\Carbon::parse($toDate)->format('M d, Y') : 'Present' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Entries Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-primary">
                        <i class="bi bi-journal-text me-2"></i>Contributing Ledger Entries
                    </h5>
                    <span class="badge bg-primary fs-6 py-2 px-3">
                        Total Sum: {{ number_format($ledgers->sum('quantity'), 2) }} {{ $item->unit }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($ledgers->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-info-circle text-muted fs-2"></i>
                            <p class="text-muted mt-2 mb-0">No ledger entries found contributing to this sum total.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Warehouse</th>
                                        <th>Action</th>
                                        <th>Quantity</th>
                                        <th>Ref Numbers / Details</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ledgers as $ledger)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold">{{ $ledger->entry_date ? $ledger->entry_date->format('M d, Y') : 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <span class="text-secondary">{{ $ledger->warehouse->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark text-uppercase border">{{ $ledger->action }}</span>
                                            </td>
                                            <td class="fw-bold text-success">
                                                {{ number_format($ledger->quantity, 2) }}
                                                <small class="text-muted fw-normal ms-1">{{ $item->unit }}</small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($ledger->po_number)
                                                        <div><span class="text-muted">PO:</span> <strong class="text-dark">{{ $ledger->po_number }}</strong></div>
                                                    @endif
                                                    @if($ledger->delivery_receipt)
                                                        <div><span class="text-muted">DR:</span> <strong class="text-dark">{{ $ledger->delivery_receipt }}</strong></div>
                                                    @endif
                                                    @if($ledger->offical_receipt)
                                                        <div><span class="text-muted">OR:</span> <strong class="text-dark">{{ $ledger->offical_receipt }}</strong></div>
                                                    @endif
                                                    @if($ledger->plate_no)
                                                        <div><span class="text-muted">Plate:</span> <strong class="text-dark">{{ $ledger->plate_no }}</strong></div>
                                                    @endif
                                                    @if($ledger->remarks)
                                                        <div class="text-muted text-truncate" style="max-width: 250px;" title="{{ $ledger->remarks }}">
                                                            <em>{{ $ledger->remarks }}</em>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="{{ route('ledgers.show', $ledger->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
