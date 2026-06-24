@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">Items Management</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('items.import.form') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Bulk Import
                </a>
                <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Item
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('items.index') }}" method="GET" class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search item name or specification..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="CONSUMABLE" {{ request('type') === 'CONSUMABLE' ? 'selected' : '' }}>Consumable</option>
                        <option value="ASSET" {{ request('type') === 'ASSET' ? 'selected' : '' }}>Asset</option>
                        <option value="RECOVERABLE" {{ request('type') === 'RECOVERABLE' ? 'selected' : '' }}>Recoverable</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Details</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $item->name }}</div>
                                <div class="small text-muted">{{ $item->specification }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->type }}</span>
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-end">
                                <a href="{{ route('items.edit', array_merge(['item' => $item->id], request()->query())) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No items found matching the criteria.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
