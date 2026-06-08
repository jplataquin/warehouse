@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">Edit Warehouse</div>
        <div class="card-body">
            <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $warehouse->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="SITE" {{ $warehouse->type === 'SITE' ? 'selected' : '' }}>SITE</option>
                        <option value="CENTRAL" {{ $warehouse->type === 'CENTRAL' ? 'selected' : '' }}>CENTRAL</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Project (Required for Site)</label>
                    <select name="project_id" class="form-select">
                        <option value="">N/A (Central)</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ $warehouse->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Active" {{ $warehouse->status === 'Active' || $warehouse->status === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="Deactivated" {{ $warehouse->status === 'Deactivated' || $warehouse->status === 'DEACTIVATED' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assigned Loggers</label>
                    <div class="card p-3">
                        @foreach($loggers as $logger)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="logger_ids[]" value="{{ $logger->id }}" id="logger_{{ $logger->id }}" 
                                    {{ $warehouse->loggers->contains($logger->id) ? 'checked' : '' }}>
                                <label class="form-check-label" for="logger_{{ $logger->id }}">
                                    {{ $logger->name }} ({{ $logger->email }})
                                </label>
                            </div>
                        @endforeach
                        @if($loggers->isEmpty())
                            <p class="text-muted mb-0">No loggers found.</p>
                        @endif
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Warehouse</button>
            </form>
        </div>
    </div>
</div>
@endsection
