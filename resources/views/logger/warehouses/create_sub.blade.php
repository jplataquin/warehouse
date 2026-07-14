@extends('layouts.logger')

@section('inner_content')
<div class="container-fluid p-0">
    <!-- Header Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4 bg-primary text-white rounded">
            <div class="d-flex align-items-center">
                <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                    <i class="bi bi-diagram-3-fill fs-3"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold">Create Sub-Warehouse</h4>
                    <p class="mb-0 opacity-75">Create a sub-warehouse under <strong>{{ $parentWarehouse->name }}</strong> to further organize your items.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Creation Form Card -->
    <div class="card border-0 shadow-sm max-w-lg">
        <div class="card-body p-4">
            <form action="{{ route('logger.sub-warehouses.store', $parentWarehouse->id) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Sub-Warehouse Name</label>
                    <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" placeholder="e.g. Rack A, Shelf 2, Area 51" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted mt-2">
                        Choose a descriptive name. The sub-warehouse will automatically inherit the parent's project structure and type (CENTRAL).
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <a href="{{ route('logger.warehouse.dashboard', $parentWarehouse->id) }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Cancel & Return
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-plus-circle me-1"></i> Create Sub-Warehouse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
