@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Project Allocation Summary</li>
                </ol>
            </nav>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-2"></i>Filter Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.project-allocation-summary') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="project_id" class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                <option value="">-- Select Project --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="from_date" class="form-label fw-bold">From Date</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label fw-bold">To Date</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="{{ route('reports.project-allocation-summary') }}" class="btn btn-outline-secondary">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            @if($selectedProject)
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="m-0 fw-bold text-primary">
                                <i class="bi bi-table me-2"></i>Allocation Summary: {{ $selectedProject->name }}
                            </h5>
                            @if($fromDate || $toDate)
                                <small class="text-muted">
                                    Period: 
                                    <strong>{{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('M d, Y') : 'Beginning' }}</strong>
                                    to 
                                    <strong>{{ $toDate ? \Carbon\Carbon::parse($toDate)->format('M d, Y') : 'Present' }}</strong>
                                </small>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($reportData->isEmpty())
                            <div class="text-center py-5">
                                <i class="bi bi-info-circle text-muted fs-2"></i>
                                <p class="text-muted mt-2 mb-0">No allocation data found for this project and date range.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Allocation Target / Item</th>
                                            <th class="text-end pe-4" style="width: 250px;">Total Quantity Allocated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reportData as $allocationId => $items)
                                            @php
                                                $allocationName = $items->first()->allocation ? $items->first()->allocation->name : 'Unspecified Target';
                                                $targetTotal = $items->sum('total_quantity');
                                            @endphp
                                            <tr class="table-light">
                                                <td colspan="2" class="ps-4 py-3 fw-bold text-dark bg-light border-bottom-0">
                                                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>{{ $allocationName }}
                                                </td>
                                            </tr>
                                            @foreach($items as $data)
                                                @if($data->item_id)
                                                    <tr onclick="window.open('{{ route('reports.project-allocation-summary.details', ['project_id' => $selectedProject->id, 'allocation_id' => $allocationId, 'item_id' => $data->item_id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}', '_blank')" style="cursor: pointer;">
                                                @else
                                                    <tr>
                                                @endif
                                                    <td class="ps-5 py-2 text-secondary fw-semibold">
                                                        <i class="bi bi-box-seam me-2 text-muted"></i>
                                                        {{ $data->item ? $data->item->name : 'Unspecified Item' }}
                                                        @if($data->item && $data->item->specification)
                                                            <span class="text-muted small ms-2">({{ $data->item->specification }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-4 py-2 text-success fw-bold">
                                                        {{ number_format($data->total_quantity, 2) }}
                                                        <small class="text-muted fw-normal ms-1">{{ $data->item ? $data->item->unit : '' }}</small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-info border-0 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> Please select a project and optional date range above to view the allocation report.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
