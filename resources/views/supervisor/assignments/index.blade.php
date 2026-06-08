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
                    <label class="form-label">Assign Warehouses</label>
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

        loggerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) {
                checkboxes.forEach(cb => cb.checked = false);
                return;
            }

            const assignedIds = JSON.parse(selectedOption.getAttribute('data-warehouses') || '[]');
            checkboxes.forEach(cb => {
                cb.checked = assignedIds.includes(parseInt(cb.value));
            });
        });
    });
</script>
@endsection
