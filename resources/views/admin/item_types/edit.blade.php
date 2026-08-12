@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="fw-bold text-secondary mb-0">
                    <i class="bi bi-tag-fill me-2 text-primary"></i>Edit Custom Item Type
                </h2>
                <a href="{{ route('item-types.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 small shadow-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <div class="card border-0 shadow-sm p-4">
                <form action="{{ route('item-types.update', $itemType->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold text-muted text-uppercase small">Type Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $itemType->name) }}" placeholder="e.g., Vehicles, IT Equipment" required autofocus {{ in_array(strtolower($itemType->name), ['consumable', 'asset']) ? 'disabled' : '' }}>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            @if(in_array(strtolower($itemType->name), ['consumable', 'asset']))
                                <div class="form-text text-warning"><i class="bi bi-exclamation-triangle-fill"></i> System-default item type name cannot be edited.</div>
                                <input type="hidden" name="name" value="{{ $itemType->name }}">
                            @else
                                <div class="form-text">Choose a unique and descriptive name.</div>
                            @endif
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="base_behavior" class="form-label fw-bold text-muted text-uppercase small">Base Behavior</label>
                        <select name="base_behavior" id="base_behavior" class="form-select form-select-lg @error('base_behavior') is-invalid @enderror" required {{ in_array(strtolower($itemType->name), ['consumable', 'asset']) ? 'disabled' : '' }}>
                            <option value="CONSUMABLE" {{ old('base_behavior', $itemType->base_behavior) === 'CONSUMABLE' ? 'selected' : '' }}>CONSUMABLE (Bulk quantities, non-serialized)</option>
                            <option value="ASSET" {{ old('base_behavior', $itemType->base_behavior) === 'ASSET' ? 'selected' : '' }}>ASSET (Individually tracked, serialized/unique)</option>
                        </select>
                        @error('base_behavior')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            @if(in_array(strtolower($itemType->name), ['consumable', 'asset']))
                                <div class="form-text text-warning"><i class="bi bi-exclamation-triangle-fill"></i> System-default base behavior cannot be edited.</div>
                                <input type="hidden" name="base_behavior" value="{{ $itemType->base_behavior }}">
                            @else
                                <div class="form-text">This dictates the core logic and validations applied when logging entries for items of this type. This cannot be easily changed once items are created.</div>
                            @endif
                        @enderror
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-save-fill"></i> Update Custom Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
