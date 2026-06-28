@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">New Item</div>
        <div class="card-body">
            <form action="{{ route('items.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE">CONSUMABLE</option>
                        <option value="ASSET">ASSET</option>
                        <option value="RECOVERABLE">RECOVERABLE</option>
                    </select>
                </div>
                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" selected>Operational</option>
                        <option value="Out of Order">Out of Order</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specification</label>
                    <input type="text" name="specification" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
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
