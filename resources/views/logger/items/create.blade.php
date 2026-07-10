@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-1"></i> Create New Item (For Review)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
                <div>
                    <strong>Notice:</strong> Any item you create as a Logger will be flagged for administrator review. You can immediately log warehouse transactions using this item once created.
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('logger.items.store') }}" method="POST">
                @csrf
                @if($warehouseId)
                    <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                @endif

                <div class="mb-3">
                    <label class="form-label fw-bold">Item Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', request('name')) }}" placeholder="e.g. Copper Cable, Cement" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE" {{ old('type', request('type')) === 'CONSUMABLE' ? 'selected' : '' }}>CONSUMABLE</option>
                        <option value="ASSET" {{ old('type', request('type')) === 'ASSET' ? 'selected' : '' }}>ASSET</option>
                    </select>
                </div>

                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label fw-bold">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" {{ old('status', request('status')) === 'Operational' ? 'selected' : '' }}>Operational</option>
                        <option value="Out of Order" {{ old('status', request('status')) === 'Out of Order' ? 'selected' : '' }}>Out of Order</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Specification</label>
                    <input type="text" name="specification" class="form-control" value="{{ old('specification', request('specification')) }}" placeholder="e.g. 10m, 50kg, 1/2 inch (Optional)">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Unit of Measure</label>
                    <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', request('unit')) }}" placeholder="e.g. pcs, bags, rolls, meters" required>
                    @error('unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Create Item
                    </button>
                    @if($warehouseId)
                        <a href="{{ route('logger.warehouse.dashboard', $warehouseId) }}" class="btn btn-secondary">Cancel</a>
                    @else
                        <a href="{{ route('home') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const statusGroup = document.getElementById('status-group');

    function toggleStatusField() {
        if (typeSelect.value === 'ASSET') {
            statusGroup.style.display = 'block';
        } else {
            statusGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', toggleStatusField);
    toggleStatusField(); // Run on load
});
</script>
@endsection
