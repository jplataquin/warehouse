@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">Review Pending Items</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-danger rounded-pill px-3 py-2">
                    {{ $items->total() }} Pending Review
                </span>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.items.review') }}" method="GET" class="row g-3 mb-4">
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
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i> Pending Review
                                    </span>
                                </div>
                                <div class="small text-muted">{{ $item->specification }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->type }}</span>
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <form action="{{ route('items.approve', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Item">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                    </form>
                                    <a href="{{ route('items.edit', array_merge(['item' => $item->id], request()->query())) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                    <a href="{{ route('items.merge.form', $item) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-arrow-left-right me-1"></i> Merge
                                    </a>
                                    <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item? This action will hide the item from future logs, though historical ledger movements will remain intact.');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No pending items found matching the criteria. All items are reviewed!</td>
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