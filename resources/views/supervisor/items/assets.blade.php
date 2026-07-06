@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="bi bi-truck me-2"></i> Asset Inventory</h2>
        <form action="{{ route('items.assets') }}" method="GET" class="d-flex" style="max-width: 400px;">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search asset name or spec..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                </button>
                @if(request('search'))
                    <a href="{{ route('items.assets') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
        @forelse($assets as $asset)
            <div class="col">
                <div class="card shadow-sm border-1 h-100" style="border-radius: 15px; overflow: hidden; transition: transform 0.2s;">
                    <div class="card-header bg-success text-white py-3 border-0">
                        <h5 class="card-title mb-0 text-truncate" title="{{ $asset->name }}">{{ $asset->name }}</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Specification</label>
                            <p class="mb-0 text-dark small text-truncate-2" style="height: 2.5rem;">{{ $asset->specification ?: 'N/A' }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Status</label>
                            @if($asset->status === 'Out of Order')
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Out of Order</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Operational</span>
                            @endif
                        </div>
                        
                        <div class="mb-3 mt-auto">
                            <div class="p-3 bg-light rounded-3 border">
                                <label class="small text-muted text-uppercase fw-bold d-block mb-2">Current Location</label>
                                @if($asset->currentWarehouse)
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                        <span class="fw-bold text-primary text-truncate">{{ $asset->currentWarehouse->name }}</span>
                                    </div>
                                    <span class="badge bg-white text-dark border small">{{ $asset->currentWarehouse->type }}</span>
                                @else
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-exclamation-circle me-2"></i>
                                        <span class="fw-bold text-primary text-truncate">Not in storage</span>
                                    </div>
                                    <span class="badge bg-white text-dark border small">N/A</span>
                                @endif

                                @if($asset->is_asset_utilized && $asset->latestUtilizeLedger)
                                    <hr class="my-2">
                                    <div class="mt-2">
                                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Assigned To</label>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-person-fill text-warning me-2"></i>
                                            <span class="fw-bold text-dark text-truncate" title="{{ $asset->latestUtilizeLedger->assigned_to }}">{{ $asset->latestUtilizeLedger->assigned_to }}</span>
                                        </div>
                                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Assignment Date</label>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event text-secondary me-2"></i>
                                            <span class="small text-dark">{{ $asset->latestUtilizeLedger->entry_date?->format('M d, Y') ?: 'N/A' }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                {{ $asset->unit }}
                            </span>
                            @if($asset->currentWarehouse)
                                <a href="{{ route('ledgers.item_history', ['warehouse' => $asset->current_warehouse_id, 'item' => $asset->id]) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                                    View History <i class="bi bi-arrow-right small"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="w-100 text-center py-5 bg-white rounded-4 shadow-sm border">
                <div class="text-muted mb-3">
                    <i class="bi bi-box-seam fs-1"></i>
                </div>
                <h4 class="fw-bold">No assets found</h4>
                <p class="text-muted">Adjust your search or add new assets to the system.</p>
                @if(Auth::user()->isAdmin() || Auth::user()->isSupervisor())
                <a href="{{ route('items.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i> Add New Item
                </a>
                @endif
            </div>
        @endforelse
    </div>
</div>

<style>
    .card:hover {
        transform: translateY(-5px);
    }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
    }
</style>
@endsection
