@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card mb-4">
        <div class="card-header">Bulk Logger Assignment</div>
        <div class="card-body">
            <form action="{{ route('assignments.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Select Logger</label>
                    <select name="user_id" class="form-select" required id="logger-select">
                        <option value="">-- Choose a Logger --</option>
                        @foreach($loggers as $logger)
                            <option value="{{ $logger->id }}" {{ old('user_id') == $logger->id ? 'selected' : '' }} data-warehouses="{{ json_encode($logger->warehouses->pluck('id')) }}">
                                {{ $logger->name }} ({{ $logger->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Assign Warehouses</label>
                        @if(!$warehouses->isEmpty())
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="select-all-warehouses">
                            <label class="form-check-label small fw-bold text-muted text-uppercase" for="select-all-warehouses">
                                Select / Deselect All
                            </label>
                        </div>
                        @endif
                    </div>
                    <div class="row">
                        @foreach($warehouses as $warehouse)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input warehouse-checkbox" type="checkbox" name="warehouse_ids[]" value="{{ $warehouse->id }}" id="warehouse_{{ $warehouse->id }}">
                                    <label class="form-check-label" for="warehouse_{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                        <small class="text-muted d-block">{{ $warehouse->type }} - {{ $warehouse->project ? $warehouse->project->name : 'Central' }}</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($warehouses->isEmpty())
                        <p class="text-muted">No warehouses found.</p>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Save Assignments</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Current Assignments Summary</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Logger</th>
                        <th>Assigned Warehouses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loggers as $logger)
                    <tr>
                        <td><strong>{{ $logger->name }}</strong><br><small>{{ $logger->email }}</small></td>
                        <td>
                            @foreach($logger->warehouses as $warehouse)
                                <span class="badge bg-secondary">{{ $warehouse->name }}</span>
                            @endforeach
                            @if($logger->warehouses->isEmpty())
                                <span class="text-muted">No warehouses assigned</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loggerSelect = document.getElementById('logger-select');
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
        }

        loggerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                checkboxes.forEach(cb => cb.checked = false);
                updateSelectAllState();
                return;
            }

            const assignedIds = JSON.parse(selectedOption.getAttribute('data-warehouses') || '[]');
            checkboxes.forEach(cb => {
                cb.checked = assignedIds.includes(parseInt(cb.value));
            });
            updateSelectAllState();
        });

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
