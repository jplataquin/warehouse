@extends('layouts.logger')

@section('inner_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Unreturned Assets</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Unreturned Assets Report</h1>
            <p class="text-muted small mb-0">Assets currently logged out for utilization or maintenance and not yet returned to any warehouse.</p>
        </div>
        <div>
            <a href="{{ route('reports.unreturned-assets.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Print Report
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.unreturned-assets') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="warehouse_id" class="form-label small fw-bold">Origin Warehouse</label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$selectedWarehouse === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="item_type_id" class="form-label small fw-bold">Asset Type</label>
                    <select name="item_type_id" id="item_type_id" class="form-select">
                        <option value="">All Asset Types</option>
                        @foreach($itemTypes as $type)
                            <option value="{{ $type->id }}" {{ (string)$selectedItemType === (string)$type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-bold">Search Asset</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or specification..." value="{{ $search }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    @if($selectedWarehouse || $selectedItemType || $search)
                        <a href="{{ route('reports.unreturned-assets') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow-sm">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary">Unreturned Asset Records</h6>
            <span class="badge bg-primary rounded-pill">{{ count($items) }} asset(s) out</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Asset Name & Specification</th>
                            <th>Asset Type</th>
                            <th>Origin Warehouse</th>
                            <th>Action</th>
                            <th>Assigned To / Plate No</th>
                            <th>Out Date</th>
                            <th class="text-center">Days Out</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $item->name }}</div>
                                    @if($item->specification)
                                        <small class="text-muted">{{ $item->specification }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->item_type }}</td>
                                <td>{{ $item->warehouse }}</td>
                                <td>
                                    @if($item->action === 'UTILIZE')
                                        <span class="badge bg-info text-dark">UTILIZE</span>
                                    @elseif($item->action === 'MAINTENANCE')
                                        <span class="badge bg-warning text-dark">MAINTENANCE</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $item->action }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $item->assigned_to }}</div>
                                    @if($item->plate_no !== '-')
                                        <small class="text-muted"><i class="bi bi-truck me-1"></i>{{ $item->plate_no }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->out_date }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->days_out >= 30 ? 'bg-danger' : ($item->days_out >= 7 ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">
                                        {{ $item->days_out }} {{ Str::plural('day', $item->days_out) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $item->remarks }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                    No unreturned assets found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
