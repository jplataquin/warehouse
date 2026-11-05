@extends('layouts.logger')

@section('inner_content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center">
            <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-exclamation-octagon fs-2 text-danger"></i>
            </div>
            <div>
                <h1 class="fw-bold mb-0 text-dark">Discrepancy Logs</h1>
                <div class="text-muted small text-uppercase fw-bold tracking-wider">
                    Diagnostic Negative Stock Anomaly Records
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="d-flex justify-content-md-end gap-2">
            <span class="badge bg-danger fs-6 px-3 py-2 shadow-sm">
                {{ $pendingCount }} Pending
            </span>
            <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">
                {{ $resolvedCount }} Resolved
            </span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 text-success"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2 text-info"></i> {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Filter Navigation Tabs --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between">
            <div class="btn-group shadow-sm" role="group" aria-label="Filter status">
                <a href="{{ route('discrepancies.index', ['status' => 'ALL']) }}" class="btn btn-sm {{ $status === 'ALL' ? 'btn-primary' : 'btn-outline-primary' }}">
                    All Logs <span class="badge bg-secondary ms-1">{{ $discrepancies->total() }}</span>
                </a>
                <a href="{{ route('discrepancies.index', ['status' => 'DISCREPANCY']) }}" class="btn btn-sm {{ $status === 'DISCREPANCY' ? 'btn-danger' : 'btn-outline-danger' }}">
                    Pending Discrepancies <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                </a>
                <a href="{{ route('discrepancies.index', ['status' => 'RESOLVED']) }}" class="btn btn-sm {{ $status === 'RESOLVED' ? 'btn-success' : 'btn-outline-success' }}">
                    Resolved Logs <span class="badge bg-success ms-1">{{ $resolvedCount }}</span>
                </a>
            </div>
            <div class="text-muted small">
                Showing {{ $discrepancies->firstItem() ?? 0 }} - {{ $discrepancies->lastItem() ?? 0 }} of {{ $discrepancies->total() }} records
            </div>
        </div>
    </div>
</div>

{{-- Discrepancies List Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="border-bottom">
                    <tr class="small text-uppercase text-muted fw-bold">
                        <th class="ps-4" style="width: 70px;">ID</th>
                        <th>Item</th>
                        <th>Warehouse</th>
                        <th>Ledger ID</th>
                        <th>Status</th>
                        <th>Detected At</th>
                        <th>Resolution Details</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discrepancies as $discrepancy)
                        <tr>
                            <td class="ps-4 fw-bold text-secondary">
                                #{{ $discrepancy->id }}
                            </td>
                            <td>
                                @if($discrepancy->item)
                                    <div class="fw-bold text-dark">{{ $discrepancy->item->name }}</div>
                                    <div class="small text-muted text-uppercase">{{ $discrepancy->item->type }} • {{ $discrepancy->item->specification ?? 'N/A' }}</div>
                                @else
                                    <span class="text-muted fst-italic">Item Deleted</span>
                                @endif
                            </td>
                            <td>
                                @if($discrepancy->warehouse)
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        <i class="bi bi-building me-1"></i> {{ $discrepancy->warehouse->name }}
                                    </span>
                                @else
                                    <span class="text-muted fst-italic">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($discrepancy->ledger)
                                    <a href="{{ route('ledgers.item_history', ['warehouse' => $discrepancy->warehouse_id, 'item' => $discrepancy->ledger_id]) }}" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Entry #{{ $discrepancy->ledger_id }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $discrepancy->ledger->action }} ({{ $discrepancy->ledger->type }} {{ $discrepancy->ledger->quantity }})
                                    </div>
                                @else
                                    <span class="text-muted">#{{ $discrepancy->ledger_id }} (Deleted)</span>
                                @endif
                            </td>
                            <td>
                                @if($discrepancy->status === 'DISCREPANCY')
                                    <span class="badge bg-danger px-2 py-1">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> DISCREPANCY
                                    </span>
                                @else
                                    <span class="badge bg-success px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> RESOLVED
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-bold text-dark">{{ $discrepancy->created_at->format('M d, Y') }}</div>
                                <div class="small text-muted">{{ $discrepancy->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                @if($discrepancy->status === 'RESOLVED')
                                    <div class="small">
                                        <i class="bi bi-person-check text-success me-1"></i> 
                                        <strong>{{ $discrepancy->resolver?->name ?? 'Authorized User' }}</strong>
                                    </div>
                                    @if($discrepancy->resolved_at)
                                        <div class="small text-muted">{{ $discrepancy->resolved_at->format('M d, Y h:i A') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small fst-italic">Unresolved anomaly</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($discrepancy->status === 'DISCREPANCY')
                                    <form action="{{ route('discrepancies.resolve', $discrepancy) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark discrepancy #{{ $discrepancy->id }} as resolved?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-success btn-sm shadow-sm">
                                            <i class="bi bi-check-lg me-1"></i> Set as Resolved
                                        </button>
                                    </form>
                                @else
                                    <span class="text-success small fw-bold">
                                        <i class="bi bi-check2-all"></i> Completed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-shield-check text-success fs-1 d-block mb-3"></i>
                                <h6 class="fw-bold text-dark">No Discrepancies Recorded</h6>
                                <p class="text-muted small mb-0">No negative stock discrepancies match the selected status filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($discrepancies->hasPages())
        <div class="card-footer border-top p-3 d-flex justify-content-center">
            {{ $discrepancies->links() }}
        </div>
    @endif
</div>
@endsection
