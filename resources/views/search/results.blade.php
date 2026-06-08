@extends('layouts.logger')

@section('inner_content')
<div class="mb-4">
    <h4 class="fw-bold"><i class="bi bi-search me-2 text-primary"></i> Search Results for: "{{ $query }}"</h4>
    <p class="text-muted">
        Found {{ $ledgers->total() }} ledger entries
        @if(!Auth::user()->isAdmin() && !Auth::user()->isSupervisor())
            and {{ $warehouses->count() }} warehouses.
        @else
            .
        @endif
    </p>
</div>

@if($warehouses->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-uppercase small text-muted">
                <i class="bi bi-geo-alt me-1"></i> Warehouses
            </h6>
        </div>
        <div class="list-group list-group-flush">
            @foreach($warehouses as $w)
                <a href="{{ route('logger.warehouse.dashboard', $w) }}" class="list-group-item list-group-item-action py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-dark">{{ $w->name }}</span>
                            <div class="small text-muted">{{ $w->type }} • {{ $w->status }}</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-uppercase small text-muted">
            <i class="bi bi-journal-text me-1"></i> Ledger Entries
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 small text-muted text-uppercase fw-bold px-4">Date</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold">Item</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold">Warehouse</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold">Allocation</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold">Reference Nos.</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold">Action/Qty</th>
                    <th class="border-0 small text-muted text-uppercase fw-bold px-4">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgers as $ledger)
                    <tr onclick="window.location='{{ route('ledgers.show', $ledger) }}'" style="cursor: pointer;" class="hover-bg-light">
                        <td class="px-4">
                            <div class="fw-bold">{{ $ledger->entry_date->format('M d, Y') }}</div>
                            <div class="small text-muted text-uppercase">{{ $ledger->type }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $ledger->item->name }} {{ $ledger->item->specification }} {{ $ledger->item->unit }} ({{ $ledger->item->type }})</div>
                        </td>
                        <td>
                            <div class="small fw-bold">{{ $ledger->warehouse->name }}</div>
                            <div class="small text-muted text-uppercase">{{ $ledger->project->name ?? 'N/A' }}</div>
                        </td>
                        <td class="small">
                            {{ $ledger->allocation->name ?? 'N/A' }}
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
                            @if($ledger->plate_no)
                                <div class="small"><span class="text-muted">Plate:</span> <span class="fw-bold">{{ $ledger->plate_no }}</span></div>
                            @endif
                            @if(!$ledger->po_number && !$ledger->delivery_receipt && !$ledger->offical_receipt && !$ledger->plate_no)
                                <span class="text-muted italic small">No references</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $ledger->action === 'IN' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill small px-2">
                                {{ $ledger->action }}
                            </span>
                            <span class="fw-bold ms-1">{{ number_format($ledger->quantity, 2) }}</span>
                            <span class="small text-muted">{{ $ledger->item->unit }}</span>
                        </td>
                        <td class="px-4 small text-muted">
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $ledger->remarks }}">
                                {{ $ledger->remarks ?? 'N/A' }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No ledger entries found matching your search.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ledgers->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $ledgers->links() }}
        </div>
    @endif
</div>
@endsection
