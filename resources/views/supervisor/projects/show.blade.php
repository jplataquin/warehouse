@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="fw-bold text-primary mb-0">{{ $project->name }}</h2>
                <div class="btn-group">
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil"></i> Edit Project
                    </a>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this project?')">
                            <i class="bi bi-trash"></i> Delete Project
                        </button>
                    </form>
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Project Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold d-block">Project Name</label>
                            <p class="fw-bold">{{ $project->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold d-block">MQMS Mapped ID</label>
                            <p class="text-muted">{{ $project->mapped_to_project_id ?? 'Not Mapped' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
