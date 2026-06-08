@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">Edit Allocation</div>
        <div class="card-body">
            <form action="{{ route('allocations.update', $allocation) }}" method="POST">
                @csrf
                @method('PUT')
                @if($allocation->mapped_to_component_id)
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">MQMS Mapped ID</label>
                    <div class="form-control bg-light"><code>{{ $allocation->mapped_to_component_id }}</code></div>
                    <small class="text-muted">This allocation is mapped to an MQMS component and cannot have its remote ID changed manually.</small>
                </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $allocation->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select" required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $allocation->warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Allocation</button>
                <a href="{{ route('warehouses.show', $allocation->warehouse_id) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
