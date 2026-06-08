@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Projects</span>
            <div class="d-flex gap-2 align-items-center">
                <form action="{{ route('projects.index') }}" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search projects..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-secondary btn-sm ms-1">Search</button>
                    @if(request('search'))
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
                    @endif
                </form>
                <div class="btn-group">
                    <a href="{{ route('projects.import.form') }}" class="btn btn-outline-success btn-sm me-2">
                        <i class="bi bi-file-earmark-excel me-1"></i> Bulk Import
                    </a>
                    <a href="{{ route('projects.mqms-import.preview') }}" class="btn btn-outline-primary btn-sm me-3" onclick="return confirm('Are you sure you want to fetch projects from MQMS? This will call the external API.')">
                        <i class="bi bi-cloud-download me-1"></i> Fetch from MQMS
                    </a>
                    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> New Project
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Project Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                    <tr class="clickable-row" data-href="{{ route('projects.show', $project) }}" style="cursor: pointer;">
                        <td class="fw-bold py-3">
                            <i class="bi bi-folder text-primary me-2"></i>
                            {{ $project->name }}
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
    const rows = document.querySelectorAll('.clickable-row');
    rows.forEach(row => {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
    });
});
</script>
@endsection
