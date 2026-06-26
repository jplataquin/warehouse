@extends('layouts.logger')

@section('inner_content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-3 mb-md-0">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-journal-text fs-2 text-primary"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-0 text-dark">General Ledger</h1>
                <div class="text-muted small text-uppercase fw-bold tracking-wider">
                    {{ $selectedWarehouse ? 'Viewing stock for ' . $selectedWarehouse->name : 'Select a warehouse to view stock' }}
                </div>
            </div>
        </div>
    </div>
  
</div>

<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body p-4">
        <form action="{{ route('global.search') }}" method="GET">
            <label class="form-label small fw-bold text-muted text-uppercase">Global Search</label>
            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-primary"></i>
                </span>
                <input type="text" name="query" class="form-control border-start-0 ps-0" placeholder="Search PO, DR, OR, Plate No..." value="{{ request('query') }}">
                <button type="submit" class="btn btn-primary px-4">Search Everything</button>
            </div>
            <div class="form-text small mt-2">
                <i class="bi bi-info-circle me-1"></i> Quick search across all records, purchase orders, delivery receipts, and official receipts.
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form action="{{ route('ledgers.index') }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Select Warehouse</label>
                    <select name="warehouse_id" class="form-select border-primary shadow-sm" onchange="this.form.submit()">
                        <option value="">-- Choose Warehouse --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Item Type</label>
                    <select name="item_type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="CONSUMABLE" {{ request('item_type') === 'CONSUMABLE' ? 'selected' : '' }}>Consumable</option>
                        <option value="ASSET" {{ request('item_type') === 'ASSET' ? 'selected' : '' }}>Asset</option>
                        <option value="RECOVERABLE" {{ request('item_type') === 'RECOVERABLE' ? 'selected' : '' }}>Recoverable</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Movement Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Movements</option>
                        <option value="IN" {{ request('type') === 'IN' ? 'selected' : '' }}>IN</option>
                        <option value="OUT" {{ request('type') === 'OUT' ? 'selected' : '' }}>OUT</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Action</label>
                    <select name="action" class="form-select" onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        <option value="TRANSFER" {{ request('action') === 'TRANSFER' ? 'selected' : '' }}>Transfer</option>
                        <option value="DELIVERY" {{ request('action') === 'DELIVERY' ? 'selected' : '' }}>Delivery</option>
                        <option value="DIRECT" {{ request('action') === 'DIRECT' ? 'selected' : '' }}>Direct</option>
                        <option value="ALLOCATE" {{ request('action') === 'ALLOCATE' ? 'selected' : '' }}>Allocate</option>
                        <option value="DISPOSE" {{ request('action') === 'DISPOSE' ? 'selected' : '' }}>Dispose</option>
                        <option value="UTILIZE" {{ request('action') === 'UTILIZE' ? 'selected' : '' }}>Utilize</option>
                        <option value="LOST" {{ request('action') === 'LOST' ? 'selected' : '' }}>Lost</option>
                        <option value="RETURN" {{ request('action') === 'RETURN' ? 'selected' : '' }}>Return</option>
                        <option value="MAINTENANCE" {{ request('action') === 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                        <option value="CORRECTION" {{ request('action') === 'CORRECTION' ? 'selected' : '' }}>Correction</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-filter"></i> Apply
                    </button>
                    <a href="{{ route('ledgers.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>

            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                @if($selectedWarehouse && isset($allocations) && $allocations->count() > 0)
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Allocation</label>
                    <select name="allocation_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Allocations</option>
                        @foreach($allocations as $alloc)
                            <option value="{{ $alloc->id }}" {{ request('allocation_id') == $alloc->id ? 'selected' : '' }}>
                                {{ $alloc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <input type="hidden" name="item_id" value="{{ request('item_id') }}">
            </div>
        </form>
    </div>
</div>

@if($selectedWarehouse)
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-box-seam me-2 text-primary"></i>Items in Stock
            <span class="badge bg-primary rounded-pill">{{ $itemsWithStock->count() }} Item(s)</span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
        @forelse($itemsWithStock as $sItem)
        <div class="col">
            @php
                $cardRoute = route('ledgers.item_history', [$selectedWarehouse->id, $sItem->id]);
            @endphp
            <a href="{{ $cardRoute }}" 
               class="card h-100 border-1 shadow-sm hover-shadow transition-all text-decoration-none">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-2 rounded">
                            <i class="bi bi-box text-primary fs-4"></i>
                        </div>
                        <span class="badge bg-light text-dark border small">{{ $sItem->type }}</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-truncate text-dark" title="{{ $sItem->name }}">{{ $sItem->name }}</h5>
                    <p class="text-muted small mb-3 text-truncate-2" style="height: 2.5rem;">{{ $sItem->specification }}</p>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted text-uppercase fw-bold tracking-tighter" style="font-size: 0.7rem;">Current Stock</div>
                            <div class="h4 mb-0 fw-bold text-primary">{{ $sItem->balance }} <span class="fs-6 fw-normal text-muted">{{ $sItem->unit }}</span></div>
                        </div>
                        <div class="btn btn-sm btn-outline-primary rounded-circle">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5 bg-light rounded">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <p class="text-muted mb-0">No items currently in stock in this warehouse.</p>
            </div>
        </div>
        @endforelse
    </div>
</div>
@else
<div class="text-center py-5 my-5">
    <div class="bg-light rounded-circle d-inline-flex p-4 mb-4">
        <i class="bi bi-building fs-1 text-secondary opacity-50"></i>
    </div>
    <h3 class="fw-bold text-dark">No Warehouse Selected</h3>
    <p class="text-muted mx-auto" style="max-width: 400px;">Please select a warehouse from the dropdown above to view its general ledger and stock levels.</p>
</div>
@endif

<style>
    .hover-shadow {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    }
    .hover-shadow:hover {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        transform: translateY(-8px);
    }
    .transition-all {
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
    }
</style>
@endsection
