@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4 border-warning">
        <div class="card-header bg-warning text-dark py-3 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <h5 class="mb-0 fw-bold">Warning: Similar Items Found</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                The item you are attempting to create is similar to one or more existing items in the database. 
                Please review the list below to ensure you are not creating a duplicate.
            </p>

            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th>Item Details</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($similarItems as $item)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->name }}</div>
                                @if($item->specification)
                                    <div class="small text-muted">{{ $item->specification }}</div>
                                @endif
                                @if(!$item->is_approved)
                                    <span class="badge bg-warning text-dark mt-1" style="font-size: 0.7rem;">Pending Review</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->type }}</span>
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ $item->status }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="alert alert-light border shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3">Your New Item Details:</h6>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase d-block">Name</span>
                        <strong class="text-dark">{{ $validated['name'] }}</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase d-block">Type</span>
                        <strong class="text-dark">{{ $validated['type'] }}</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase d-block">Specification</span>
                        <strong class="text-dark">{{ $validated['specification'] ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <span class="text-muted small text-uppercase d-block">Unit</span>
                        <strong class="text-dark">{{ $validated['unit'] }}</strong>
                    </div>
                </div>
            </div>

            <form action="{{ route('logger.items.store') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="name" value="{{ $validated['name'] }}">
                <input type="hidden" name="type" value="{{ $validated['type'] }}">
                <input type="hidden" name="specification" value="{{ $validated['specification'] ?? '' }}">
                <input type="hidden" name="unit" value="{{ $validated['unit'] }}">
                <input type="hidden" name="status" value="{{ $validated['status'] ?? 'Operational' }}">
                @if($warehouseId)
                    <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4 fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> Yes, Proceed and Create
                    </button>
                    
                    <a href="{{ route('logger.items.create', [
                        'warehouse_id' => $warehouseId,
                        'name' => $validated['name'],
                        'type' => $validated['type'],
                        'specification' => $validated['specification'] ?? '',
                        'unit' => $validated['unit'],
                        'status' => $validated['status'] ?? ''
                    ]) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle-fill me-1"></i> No, Cancel and Edit
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
