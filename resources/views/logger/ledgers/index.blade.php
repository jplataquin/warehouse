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
    <div class="col-md-4 text-md-end">
        @if($selectedWarehouse)
        <a href="{{ route('logger.warehouse.dashboard', $selectedWarehouse->id) }}" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
        @endif
    </div>
</div>

@if($item)
<div class="card shadow-sm border-0 mb-4 bg-primary text-white">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-{{ $item->type === 'ASSET' ? '5' : '8' }}">
                <div class="d-flex align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1">{{ $item->name }}</h4>
                        <div class="opacity-75 small text-uppercase fw-bold tracking-wider">
                            {{ $item->type }} • {{ $item->specification ?? 'No Specification' }}
                        </div>
                    </div>
                </div>
            </div>
            @if($item->type === 'ASSET')
            <div class="col-md-3 mt-3 mt-md-0">
                <form action="{{ route('items.update-status', $item) }}" method="POST" class="d-inline-block text-start w-100">
                    @csrf
                    @method('PATCH')
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Asset Status</div>
                    <select name="status" class="form-select form-select-sm bg-white text-dark border-0 fw-bold py-2" onchange="this.form.submit()">
                        <option value="Operational" {{ $item->status === 'Operational' ? 'selected' : '' }}>Operational</option>
                        <option value="Out of Order" {{ $item->status === 'Out of Order' ? 'selected' : '' }}>Out of Order</option>
                    </select>
                </form>
            </div>
            @endif
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="small text-white-50 text-uppercase fw-bold mb-1">Current Stock in {{ $selectedWarehouse->name }}</div>
                <div class="h2 mb-0 fw-bold">
                    {{ number_format($balance, 2) }} <span class="fs-5 fw-normal opacity-75">{{ $item->unit }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form action="{{ route('ledgers.index') }}" method="GET">
            <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Movement Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Movements</option>
                        <option value="IN" {{ request('type') === 'IN' ? 'selected' : '' }}>IN</option>
                        <option value="OUT" {{ request('type') === 'OUT' ? 'selected' : '' }}>OUT</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Action</label>
                    <select name="action" class="form-select" onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        <option value="TRANSFER" {{ request('action') === 'TRANSFER' ? 'selected' : '' }}>Transfer</option>
                        <option value="DELIVERY" {{ request('action') === 'DELIVERY' ? 'selected' : '' }}>Delivery</option>
                        <option value="ASSET_RETURN" {{ request('action') === 'ASSET_RETURN' ? 'selected' : '' }}>Asset Return</option>
                        <option value="ALLOCATE" {{ request('action') === 'ALLOCATE' ? 'selected' : '' }}>Allocate</option>
                        <option value="DISPOSE" {{ request('action') === 'DISPOSE' ? 'selected' : '' }}>Dispose</option>
                        <option value="UTILIZE" {{ request('action') === 'UTILIZE' ? 'selected' : '' }}>Utilize</option>
                        <option value="LOST" {{ request('action') === 'LOST' ? 'selected' : '' }}>Lost</option>
                        <option value="REJECT" {{ request('action') === 'REJECT' ? 'selected' : '' }}>Reject</option>
                        <option value="MAINTENANCE" {{ request('action') === 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                        <option value="CORRECTION" {{ request('action') === 'CORRECTION' ? 'selected' : '' }}>Correction</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-filter"></i> Apply
                    </button>
                    <a href="{{ route('ledgers.index', ['warehouse_id' => request('warehouse_id')]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>

            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                @if($selectedWarehouse && isset($allocations) && $allocations->count() > 0)
                <div class="col-md-4">
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
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-secondary text-uppercase mb-0 fs-6">
            <i class="bi bi-list-ul me-1"></i> Movement Records {{ $item ? ' - ' . $item->name : '' }}
        </h5>
        <div class="d-flex gap-2">
            @if($item)
                <a href="{{ route('ledgers.item_history.print', ['warehouse' => $selectedWarehouse->id, 'item' => $item->id] + request()->query()) }}" 
                   target="_blank" class="btn btn-outline-dark btn-sm shadow-sm">
                    <i class="bi bi-printer me-1"></i> Print Ledger
                </a>
            @endif
            <a href="{{ route('ledgers.create', ['warehouse_id' => request('warehouse_id'), 'item_id' => request('item_id')]) }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Entry
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Type</th>
                        <th>Action</th>
                        <th>Allocation</th>
                        @if(!$item)
                            <th>Item</th>
                        @endif
                        <th>Qty</th>
                        <th>Running Balance</th>
                        <th class="pe-4">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBal = $openingBalance; @endphp
                    @forelse($ledgers as $ledger)
                    @php
                        if ($item) {
                            if ($ledger->type === 'IN') {
                                $runningBal += $ledger->quantity;
                            } else {
                                $runningBal -= $ledger->quantity;
                            }
                        }
                    @endphp
                    <tr onclick="window.location='{{ route('ledgers.show', $ledger) }}'" style="cursor: pointer;" class="hover-bg-light">
                        <td class="ps-4">
                            <div class="fw-bold">{{ $ledger->entry_date ? $ledger->entry_date->format('M d, Y') : 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $ledger->type === 'IN' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                {{ $ledger->type }}
                            </span>
                        </td>
                        <td>
                            <span class="small fw-bold text-uppercase px-2 py-1 bg-light rounded border">{{ $ledger->action }}</span>
                        </td>
                        <td class="small">
                            {{ $ledger->allocation->name ?? 'N/A' }}
                        </td>
                        @if(!$item)
                            <td class="small">
                                <div class="fw-bold">{{ $ledger->item->name }}</div>
                                <div class="text-muted text-truncate" style="max-width: 200px;">{{ $ledger->item->specification }}</div>
                            </td>
                        @endif
                        <td class="fw-bold">{{ $ledger->quantity }} <small class="text-muted fw-normal">{{ $ledger->item->unit }}</small></td>
                        <td class="fw-bold {{ $item ? 'text-primary' : 'text-muted' }}">
                            @if($item)
                                {{ number_format($runningBal, 2) }} <small class="fw-normal">{{ $ledger->item->unit }}</small>
                            @else
                                <span class="small opacity-50">N/A</span>
                            @endif
                        </td>
                        <td class="pe-4 small text-muted">
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $ledger->remarks }}">
                                {{ $ledger->remarks ?? 'N/A' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-2 d-block mb-3"></i>
                            No movements found matching the criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $ledgers->appends(request()->query())->links() }}
    </div>
    @endif
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
