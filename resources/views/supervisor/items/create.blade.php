@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">New Item</div>
        <div class="card-body">
            <form action="{{ route('items.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE">CONSUMABLE</option>
                        <option value="ASSET">ASSET</option>
                        <option value="RECOVERABLE">RECOVERABLE</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specification</label>
                    <input type="text" name="specification" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
