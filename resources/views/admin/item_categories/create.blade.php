@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="fw-bold text-secondary mb-0">
                    <i class="bi bi-folder-plus me-2 text-primary"></i>Create Item Category
                </h2>
                <a href="{{ route('item-categories.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 small shadow-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <div class="card border-0 shadow-sm p-4">
                <form action="{{ route('item-categories.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold text-muted text-uppercase small">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g., Heavy Equipment, Power Tools, Office Furniture" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text">Choose a unique and descriptive category name.</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-plus-circle-fill"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
