@extends('layouts.logger')

@section('inner_content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-trash3 text-danger me-2"></i>Pending Item Disposals
            </h3>
            <p class="text-muted small mb-0">Review, approve, or reject items marked for disposal.</p>
        </div>
        <div>
            <span class="badge bg-danger rounded-pill fs-6 px-3 py-2">
                {{ $disposals->total() }} Pending
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="ps-4">Date</th>
                            <th scope="col">Item</th>
                            <th scope="col">Type</th>
                            <th scope="col">Warehouse</th>
                            <th scope="col">Entry ID</th>
                            <th scope="col" class="text-end">Quantity</th>
                            <th scope="col">Remarks / Reason</th>
                            <th scope="col">Requested By</th>
                            <th scope="col" class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($disposals as $disposal)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">{{ $disposal->entry_date ? $disposal->entry_date->format('Y-m-d') : $disposal->created_at->format('Y-m-d') }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $disposal->item ? $disposal->item->name : 'N/A' }}</div>
                                    @if($disposal->item && $disposal->item->specification)
                                        <small class="text-muted">{{ $disposal->item->specification }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($disposal->item)
                                        <span class="badge {{ $disposal->item->type === 'ASSET' ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ $disposal->item->type }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $disposal->warehouse ? $disposal->warehouse->name : 'N/A' }}
                                </td>
                                <td>
                                    <a href="{{ route('ledgers.show', $disposal) }}" class="fw-bold text-decoration-none">
                                        #{{ $disposal->id }}
                                    </a>
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($disposal->quantity, 2) }} {{ $disposal->item ? $disposal->item->unit : '' }}
                                </td>
                                <td>
                                    <span class="text-muted">{{ $disposal->remarks ?: 'None' }}</span>
                                </td>
                                <td>
                                    <span class="small">{{ $disposal->creator ? $disposal->creator->name : 'System' }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <form action="{{ route('ledgers.approve', $disposal) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve this disposal?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-3">
                                                <i class="bi bi-check-lg me-1"></i> Approve
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $disposal->id }}">
                                            <i class="bi bi-x-lg me-1"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Rejection Modal -->
                                    <div class="modal fade text-start" id="rejectModal{{ $disposal->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $disposal->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger">
                                                    <h5 class="modal-title fw-bold" id="rejectModalLabel{{ $disposal->id }}" style="color: #ffffff;">
                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Reject Disposal
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('ledgers.destroy', $disposal) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_to" value="{{ route('ledgers.disposals') }}">
                                                    <div class="modal-body p-4">
                                                        <p class="text-dark">
                                                            Are you sure you want to reject this disposal for <strong>{{ $disposal->item ? $disposal->item->name : 'Item' }}</strong>? 
                                                            This will soft delete the disposal ledger record and retain the stock balance.
                                                        </p>

                                                        <div class="mb-3">
                                                            <label for="delete_reason_{{ $disposal->id }}" class="form-label small fw-bold text-uppercase text-muted">
                                                                Reason for Rejection <span class="text-danger">*</span>
                                                            </label>
                                                            <textarea class="form-control" id="delete_reason_{{ $disposal->id }}" name="delete_reason" rows="3" required minlength="5" placeholder="Please state why this disposal is being rejected (minimum 5 characters)..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                    No pending item disposals found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($disposals->hasPages())
            <div class="card-footer py-3">
                {{ $disposals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
