@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">Edit Project</div>
        <div class="card-body">
            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $project->name }}" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Project</button>
            </form>
        </div>
    </div>
</div>
@endsection
