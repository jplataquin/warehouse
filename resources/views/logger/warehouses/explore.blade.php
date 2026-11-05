@extends('layouts.logger')

@section('inner_content')
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <div class="d-flex align-items-center">
            <div class="p-3 rounded-circle me-3 border border-primary border-opacity-25">
                <i class="bi bi-compass fs-2 text-primary"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-1">Explore Other Warehouses</h2>
                <p class="text-muted mb-0 small">Browse active warehouses not assigned to you with view-only permissions.</p>
            </div>
        </div>
    </div>
    <div class="col-md-5 mt-3 mt-md-0">
        <form action="{{ route('logger.warehouses.explore') }}" method="GET" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by warehouse, type, or project..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('logger.warehouses.explore') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($otherWarehouses as $wh)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 border-top border-primary border-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-secondary">{{ $wh->type }}</span>
                        <span class="badge bg-warning text-dark"><i class="bi bi-eye me-1"></i> View Only</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-truncate" title="{{ $wh->name }}">{{ $wh->name }}</h5>
                    <div class="text-muted small mb-3 text-truncate" title="{{ $wh->project ? $wh->project->name : 'No Project Assigned' }}">
                        <i class="bi bi-briefcase me-1"></i> {{ $wh->project ? $wh->project->name : 'No Project' }}
                    </div>

                    @if($wh->children && $wh->children->isNotEmpty())
                        <div class="small text-muted mb-3">
                            <i class="bi bi-diagram-3 me-1"></i> <strong>{{ $wh->children->count() }}</strong> Sub-Warehouse(s)
                        </div>
                    @endif
                </div>
                <div class="card-footer border-top-0 pt-0 pb-3">
                    <a href="{{ route('logger.warehouse.dashboard', $wh->id) }}" class="btn btn-outline-primary btn-sm w-100 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Explore Warehouse
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 rounded border">
                <i class="bi bi-building fs-1 text-muted d-block mb-3"></i>
                <h5 class="fw-bold">No Other Warehouses Found</h5>
                <p class="text-muted mb-0 small">
                    @if(request('search'))
                        No warehouses match your search query "{{ request('search') }}".
                    @else
                        You currently have access to all active warehouses, or none are available.
                    @endif
                </p>
            </div>
        </div>
    @endforelse
</div>

@if($otherWarehouses->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $otherWarehouses->links() }}
    </div>
@endif
@endsection
