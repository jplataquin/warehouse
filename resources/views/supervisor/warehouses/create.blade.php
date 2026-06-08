@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">New Warehouse</div>
        <div class="card-body">
            <form action="{{ route('warehouses.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="SITE">SITE</option>
                        <option value="CENTRAL">CENTRAL</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Project (Required for Site)</label>
                    <select name="project_id" class="form-select">
                        <option value="">N/A (Central)</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Active" selected>Active</option>
                        <option value="Deactivated">Deactivated</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assigned Loggers</label>
                    <div class="card p-3">
                        @foreach($loggers as $logger)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="logger_ids[]" value="{{ $logger->id }}" id="logger_{{ $logger->id }}" 
                                    {{ is_array(old('logger_ids')) && in_array($logger->id, old('logger_ids')) ? 'checked' : '' }}>
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

                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
