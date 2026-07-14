@extends('layouts.logger')

@section('inner_content')
<style>
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background-color: rgba(0,0,0,.075);
    }
</style>

<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Warehouses</span>
            <div class="d-flex gap-2">
                <form action="{{ route('warehouses.index') }}" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search warehouses..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary btn-sm ms-1">Search</button>
                    @if(request('search'))
                        <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
                    @endif
                </form>
                <a href="{{ route('warehouses.import.form') }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Bulk Import
                </a>
                <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">New Warehouse</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Project</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($warehouses as $warehouse)
                    <tr class="clickable-row" onclick="window.location='{{ route('warehouses.show', $warehouse) }}'">
                        <td>
                            @if($warehouse->parent)
                                <span class="ps-3 text-muted"><i class="bi bi-arrow-return-right me-1"></i> {{ $warehouse->name }}</span>
                                <small class="text-muted d-block ps-4 ms-2">Sub of: {{ $warehouse->parent->name }}</small>
                            @else
                                <span class="fw-bold">{{ $warehouse->name }}</span>
                            @endif
                        </td>
                        <td>{{ $warehouse->type }}</td>
                        <td>{{ $warehouse->project ? $warehouse->project->name : 'N/A' }}</td>
                        <td>{{ $warehouse->status }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
