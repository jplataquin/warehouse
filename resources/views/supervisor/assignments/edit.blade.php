@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Assign Warehouses to {{ $user->name }}</h5>
            <span class="badge bg-secondary">{{ strtoupper($user->role) }}</span>
        </div>
        <div class="card-body p-4">
            <div class="mb-4">
                <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Managing warehouse assignment scope for <strong>{{ $user->name }}</strong> ({{ $user->email }}).</p>
            </div>

            <form action="{{ route('users.assignments.update', $user) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label mb-0 fw-bold text-secondary text-uppercase fs-6">Select Authorized Warehouses</label>
                        @if(!$warehouses->isEmpty())
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="select-all-warehouses">
                            <label class="form-check-label small fw-bold text-muted text-uppercase" for="select-all-warehouses" style="cursor: pointer;">
                                Select / Deselect All
                            </label>
                        </div>
                        @endif
                    </div>
                    <div class="row g-3">
                        @foreach($warehouses as $warehouse)
                            <div class="col-md-4 mb-2">
                                <div class="card p-3 border shadow-sm h-100" style="border-radius: 8px;">
                                    <div class="form-check">
                                        <input class="form-check-input warehouse-checkbox" type="checkbox" name="warehouse_ids[]" value="{{ $warehouse->id }}" id="warehouse_{{ $warehouse->id }}" {{ $user->warehouses->contains($warehouse->id) ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="warehouse_{{ $warehouse->id }}" style="cursor: pointer;">
                                            <div class="fw-bold text-dark">{{ $warehouse->name }}</div>
                                            <small class="text-muted d-block mt-1">{{ $warehouse->type }} - {{ $warehouse->project ? $warehouse->project->name : 'Central' }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($warehouses->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i> No active warehouses found in the system.
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Save Assignments
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.warehouse-checkbox');
        const selectAllCheckbox = document.getElementById('select-all-warehouses');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    updateSelectAllState();
                });
            });

            // Initialize select all state on page load
            updateSelectAllState();
        }

        function updateSelectAllState() {
            if (!selectAllCheckbox) return;
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const allUnchecked = Array.from(checkboxes).every(c => !c.checked);
            if (allChecked && checkboxes.length > 0) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (allUnchecked) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
    });
</script>
@endsection
