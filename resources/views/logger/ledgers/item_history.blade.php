@extends('layouts.logger')

@section('inner_content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <a href="{{ route('ledgers.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary btn-sm me-3 rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-clock-history fs-2 text-primary"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-0 text-dark">{{ $item->name }}</h1>
                <div class="text-muted small text-uppercase fw-bold tracking-wider">
                    <i class="bi bi-building me-1"></i> {{ $warehouse->name }} • {{ $item->specification }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('logger.warehouse.dashboard', $warehouse->id) }}" class="btn btn-outline-primary shadow-sm mb-3">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <div class="small text-white-50 text-uppercase fw-bold">Stock on Hand</div>
                        <div class="h3 mb-0 fw-bold">{{ $balance }} <span class="fs-6 fw-normal opacity-75">{{ $item->unit }}</span></div>
                    </div>
                    <i class="bi bi-stack fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form action="{{ route('ledgers.item_history', ['warehouse' => $warehouse->id, 'item' => $item->id]) }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Entry Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="IN" {{ request('type') === 'IN' ? 'selected' : '' }}>IN</option>
                    <option value="OUT" {{ request('type') === 'OUT' ? 'selected' : '' }}>OUT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Action</label>
                <select name="action" class="form-select">
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
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase">Allocation</label>
                <select name="allocation_id" class="form-select">
                    <option value="">All Allocations</option>
                    @foreach($allocations as $alloc)
                        <option value="{{ $alloc->id }}" {{ request('allocation_id') == $alloc->id ? 'selected' : '' }}>
                            {{ $alloc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="{{ route('ledgers.item_history', ['warehouse' => $warehouse->id, 'item' => $item->id]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-secondary text-uppercase mb-0 fs-6">
            <i class="bi bi-list-ul me-1"></i> Movement History
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('ledgers.item_history.print', ['warehouse' => $warehouse->id, 'item' => $item->id] + request()->query()) }}" 
               target="_blank" class="btn btn-outline-dark btn-sm shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Ledger
            </a>
            <a href="{{ route('ledgers.create', ['warehouse_id' => $warehouse->id, 'item_id' => $item->id]) }}" class="btn btn-primary btn-sm shadow-sm">
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
                        <th>Qty</th>
                        <th>Running Balance</th>
                        <th>Reference Nos.</th>
                        <th class="pe-4">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @php $runningBal = $openingBalance; @endphp
                    @forelse($ledgers as $ledger)
                    @php
                        if ($ledger->type === 'IN') {
                            $runningBal += $ledger->quantity;
                        } else {
                            $runningBal -= $ledger->quantity;
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
                        <td class="fw-bold">
                            {{ $ledger->quantity }} <small class="text-muted fw-normal">{{ $item->unit }}</small>
                        </td>
                        <td class="fw-bold text-primary">
                            {{ number_format($runningBal, 2) }} <small class="text-muted fw-normal">{{ $item->unit }}</small>
                        </td>
                        <td>
                            @if($ledger->po_number)
                                <div class="small"><span class="text-muted">PO:</span> <span class="fw-bold">{{ $ledger->po_number }}</span></div>
                            @endif
                            @if($ledger->delivery_receipt)
                                <div class="small"><span class="text-muted">DR:</span> <span class="fw-bold">{{ $ledger->delivery_receipt }}</span></div>
                            @endif
                            @if($ledger->offical_receipt)
                                <div class="small"><span class="text-muted">OR:</span> <span class="fw-bold">{{ $ledger->offical_receipt }}</span></div>
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
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-2 d-block mb-3"></i>
                            No movements found for this item in this warehouse.
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
@endsection
