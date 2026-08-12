@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold text-secondary">
                <i class="bi bi-tags-fill me-2 text-primary"></i>Custom Item Types
            </h2>
            <p class="text-muted">Manage custom item types and define whether they behave as Consumables (bulk) or Assets (individually tracked).</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('item-types.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i> Create Custom Type
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
                            <th style="width: 35%;">Type Name</th>
                            <th style="width: 25%;">Base Behavior</th>
                            <th style="width: 15%;">Items Count</th>
                            <th class="pe-4 text-end" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemTypes as $type)
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#{{ $type->id }}</td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $type->name }}</span>
                                </td>
                                <td>
                                    @if($type->base_behavior === 'ASSET')
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            <i class="bi bi-truck me-1"></i> Asset
                                        </span>
                                    @else
                                        <span class="badge bg-success rounded-pill px-3 py-2">
                                            <i class="bi bi-box-seam me-1"></i> Consumable
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill px-2.5 py-1.5">{{ $type->items_count }}</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('item-types.edit', $type->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </a>

                                        @if(!in_array(strtolower($type->name), ['consumable', 'asset']))
                                            <form action="{{ route('item-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this custom item type? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" {{ $type->items_count > 0 ? 'disabled' : '' }}>
                                                    <i class="bi bi-trash3-fill"></i> Delete
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="Default System Type cannot be deleted">
                                                <i class="bi bi-lock-fill"></i> Lock
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-tags fs-1 d-block mb-3"></i>
                                    No custom item types found.
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
