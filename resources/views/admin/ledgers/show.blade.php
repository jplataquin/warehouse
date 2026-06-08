@extends('layouts.logger')

@section('inner_content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
            <i class="bi bi-info-circle fs-2 text-primary"></i>
        </div>
        <div>
            <h1 class="fw-bold mb-0 text-dark">Movement Details</h1>
            <div class="text-muted small text-uppercase fw-bold tracking-wider">
                ID: #{{ $ledger->id }} | Status: <span class="text-{{ $ledger->status === 'APPROVED' ? 'primary' : 'warning' }}">{{ $ledger->status }}</span>
            </div>
        </div>
    </div>
    <a href="{{ route('ledgers.index', ['warehouse_id' => $ledger->warehouse_id, 'item_id' => $ledger->item_id]) }}" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-secondary text-uppercase small tracking-wide">Primary Information</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold d-block">Item Details</label>
                        <div class="h5 fw-bold mb-0 text-primary">{{ $ledger->item->name }} {{ $ledger->item->specification }} {{ $ledger->item->unit }} ({{ $ledger->item->type }})</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold d-block">Quantity</label>
                        <div class="h5 fw-bold mb-0">{{ $ledger->quantity }} {{ $ledger->item->unit }}</div>
                        <div class="badge {{ $ledger->type === 'IN' ? 'bg-success' : 'bg-danger' }}">{{ $ledger->type }} - {{ $ledger->action }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold d-block">Warehouse</label>
                        <div class="fw-bold">{{ $ledger->warehouse->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold d-block">Entry Date</label>
                        <div class="fw-bold">{{ $ledger->entry_date ? $ledger->entry_date->format('M d, Y') : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($ledger->po_number || $ledger->delivery_receipt || $ledger->offical_receipt || $ledger->remarks)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-secondary text-uppercase small tracking-wide">References & Notes</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    @if($ledger->po_number)
                    <div class="col-md-4">
                        <label class="text-muted small text-uppercase fw-bold d-block">PO Number</label>
                        <div class="fw-bold text-dark">{{ $ledger->po_number }}</div>
                    </div>
                    @endif
                    @if($ledger->delivery_receipt)
                    <div class="col-md-4">
                        <label class="text-muted small text-uppercase fw-bold d-block">Delivery Receipt</label>
                        <div class="fw-bold text-dark">{{ $ledger->delivery_receipt }}</div>
                    </div>
                    @endif
                    @if($ledger->offical_receipt)
                    <div class="col-md-4">
                        <label class="text-muted small text-uppercase fw-bold d-block">Official Receipt</label>
                        <div class="fw-bold text-dark">{{ $ledger->offical_receipt }}</div>
                    </div>
                    @endif
                    @if($ledger->remarks)
                    <div class="col-12">
                        <label class="text-muted small text-uppercase fw-bold d-block">Remarks</label>
                        <div class="p-3 bg-light rounded text-dark mt-1" style="white-space: pre-wrap;">{{ $ledger->remarks }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-secondary text-uppercase small tracking-wide">Extended Details</span>
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Project</label>
                        <div class="fw-bold">{{ $ledger->project->name ?? ($ledger->warehouse->project->name ?? 'N/A') }}</div>
                    </li>
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Allocation</label>
                        <div class="fw-bold">
                            {{ $ledger->allocation->name ?? 'N/A' }}
                            @if($ledger->allocation && $ledger->allocation->mapped_to_component_id)
                                <code class="small ms-2">({{ $ledger->allocation->mapped_to_component_id }})</code>
                            @endif
                        </div>
                    </li>
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Source Warehouse</label>
                        <div class="fw-bold">{{ $ledger->sourceWarehouse->name ?? 'N/A' }}</div>
                    </li>
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Destination Warehouse</label>
                        <div class="fw-bold">{{ $ledger->destinationWarehouse->name ?? 'N/A' }}</div>
                    </li>
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Assigned To</label>
                        <div class="fw-bold">{{ $ledger->assigned_to ?? 'N/A' }}</div>
                    </li>
                    <li class="mb-0">
                        <label class="text-muted small text-uppercase fw-bold d-block">Plate No.</label>
                        <div class="fw-bold">{{ $ledger->plate_no ?? 'N/A' }}</div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-secondary text-uppercase small tracking-wide">Audit Information</span>
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold d-block">Created By</label>
                        <div class="fw-bold text-dark">{{ $ledger->creator->name ?? 'N/A' }}</div>
                        <div class="small text-muted">{{ $ledger->created_at->format('M d, Y H:i') }}</div>
                    </li>
                    <li class="mb-0">
                        <label class="text-muted small text-uppercase fw-bold d-block">Last Updated By</label>
                        <div class="fw-bold text-dark">{{ $ledger->updater->name ?? 'N/A' }}</div>
                        <div class="small text-muted">{{ $ledger->updated_at->format('M d, Y H:i') }}</div>
                    </li>
                </ul>
            </div>
        </div>

        @if(auth()->user()->isAdmin() && $ledger->status !== 'APPROVED')
        <div class="card border-0 shadow-sm border-top border-primary border-4">
            <div class="card-body p-4 text-center">
                <h5 class="fw-bold mb-3">Admin Actions</h5>
                <form action="{{ route('ledgers.approve', $ledger) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">
                        <i class="bi bi-check-circle"></i> Approve Entry
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
