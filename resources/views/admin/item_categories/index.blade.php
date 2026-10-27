@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold text-secondary">
                <i class="bi bi-folder2-open me-2 text-primary"></i>Item Categories
            </h2>
            <p class="text-muted">Manage item categories used to classify and organize assets.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('item-categories.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i> Create Category
            </a>
            <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 shadow-sm ms-2">
                <i class="bi bi-arrow-left"></i> Back to Settings
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 10%;">ID</th>
                            <th style="width: 55%;">Category Name</th>
                            <th style="width: 15%;">Items Count</th>
                            <th class="pe-4 text-end" style="width: 20%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#{{ $category->id }}</td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $category->name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill px-2.5 py-1.5">{{ $category->items_count }}</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('item-categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </a>

                                        <form action="{{ route('item-categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this category? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" {{ $category->items_count > 0 ? 'disabled' : '' }}>
                                                <i class="bi bi-trash3-fill"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-1 d-block mb-3"></i>
                                    No item categories found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
