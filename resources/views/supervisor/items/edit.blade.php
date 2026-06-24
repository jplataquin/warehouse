@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">Edit Item</div>
        <div class="card-body">
            <form action="{{ route('items.update', array_merge(['item' => $item->id], request()->query())) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE" {{ $item->type === 'CONSUMABLE' ? 'selected' : '' }}>CONSUMABLE</option>
                        <option value="ASSET" {{ $item->type === 'ASSET' ? 'selected' : '' }}>ASSET</option>
                        <option value="RECOVERABLE" {{ $item->type === 'RECOVERABLE' ? 'selected' : '' }}>RECOVERABLE</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specification</label>
                    <input type="text" name="specification" class="form-control" value="{{ $item->specification }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="{{ $item->unit }}" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Item</button>
                <a href="{{ route('items.index', request()->query()) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
