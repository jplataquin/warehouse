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
                <div class="{{ $selectedWarehouse ? 'col-md-4' : 'col-md-5' }}">
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

                <div class="{{ $selectedWarehouse ? 'col-md-3' : 'col-md-4' }}">
                    <label class="form-label small fw-bold text-muted text-uppercase">Item Type</label>
                    <select name="item_type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="CONSUMABLE" {{ request('item_type') === 'CONSUMABLE' ? 'selected' : '' }}>Consumable</option>
                        <option value="ASSET" {{ request('item_type') === 'ASSET' ? 'selected' : '' }}>Asset</option>
                        
                    </select>
                </div>

                @if($selectedWarehouse)
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Search Item</label>
                    <input type="text" name="item_search" class="form-control" placeholder="Search item or spec..." value="{{ request('item_search') }}">
                </div>
                @endif

                <div class="{{ $selectedWarehouse ? 'col-md-2' : 'col-md-3' }} d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-filter"></i> Apply
                    </button>
                    <a href="{{ route('ledgers.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
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
            <div class="card shadow-sm border-1 h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title fw-bold text-truncate mb-0" title="{{ $sItem->name }} {{ $sItem->specification }}">
                            @if($sItem->type === 'CONSUMABLE')
                                <span class="badge bg-danger me-1">C</span>
                            
                            @elseif($sItem->type === 'ASSET')
                                <span class="badge bg-success me-1">A</span>
                            @endif
                            {{ $sItem->name }} {{ $sItem->specification }}
                        </h6>
                    </div>
                    <div class="mt-2">
                        <div class="small text-muted text-uppercase fw-bold">Stock Level</div>
                        <div class="h4 mb-0 fw-bold text-primary">
                            {{ $sItem->balance }} <span class="fs-6 text-muted fw-normal">{{ $sItem->unit }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                    <a href="{{ $cardRoute }}" class="btn btn-sm btn-link p-0 text-decoration-none small">
                        <i class="bi bi-clock-history me-1"></i> View History
                    </a>
                </div>
            </div>
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
