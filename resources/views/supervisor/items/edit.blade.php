@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">Edit Item</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('items.update', array_merge(['item' => $item->id], request()->query())) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE" {{ $item->type === 'CONSUMABLE' ? 'selected' : '' }}>CONSUMABLE</option>
                        <option value="ASSET" {{ $item->type === 'ASSET' ? 'selected' : '' }}>ASSET</option>
                        
                    </select>
                </div>
                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" {{ $item->status === 'Operational' ? 'selected' : '' }}>Operational</option>
                        <option value="Out of Order" {{ $item->status === 'Out of Order' ? 'selected' : '' }}>Out of Order</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specification</label>
                    <input type="text" name="specification" class="form-control" value="{{ $item->specification }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="{{ $item->unit }}" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_approved" class="form-check-input" id="is_approved" value="1" {{ old('is_approved', $item->is_approved) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-success" for="is_approved">Approve/Verify Item</label>
                    <div class="form-text">Check this to mark this item as approved and verified.</div>
                </div>
                <button type="submit" class="btn btn-primary">Update Item</button>
                <a href="{{ route('items.index', request()->query()) }}" class="btn btn-secondary">Cancel</a>
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
