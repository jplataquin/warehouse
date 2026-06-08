@extends('layouts.logger')

@section('inner_content')
<div class="container-fluid py-4 px-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-eye me-2"></i> Review Import Data</h5>
                <p class="text-muted small mb-0 mt-1">Please review the items parsed from your file before finalizing.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('items.import.form') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Re-upload
                </a>
                @php
                    $validCount = collect($previewData)->where('is_valid', true)->count();
                @endphp
                <form action="{{ route('items.import.store') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-4 shadow-sm" {{ $validCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-check2-circle me-1"></i> Import {{ $validCount }} Valid Items
                    </button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 small text-muted text-uppercase fw-bold px-4">Row</th>
                        <th class="border-0 small text-muted text-uppercase fw-bold">Type</th>
                        <th class="border-0 small text-muted text-uppercase fw-bold">Name</th>
                        <th class="border-0 small text-muted text-uppercase fw-bold">Specification</th>
                        <th class="border-0 small text-muted text-uppercase fw-bold">Unit</th>
                        <th class="border-0 small text-muted text-uppercase fw-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($previewData as $item)
                        <tr class="{{ $item['is_valid'] ? '' : 'table-danger-subtle' }}">
                            <td class="px-4 fw-bold text-muted">{{ $item['row_number'] }}</td>
                            <td>
                                <span class="badge {{ $item['is_valid'] ? 'bg-secondary' : 'bg-danger' }} small">
                                    {{ $item['type'] ?: 'MISSING' }}
                                </span>
                            </td>
                            <td>{{ $item['name'] ?: '---' }}</td>
                            <td>{{ $item['specification'] ?: '---' }}</td>
                            <td>{{ $item['unit'] ?: '---' }}</td>
                            <td>
                                @if($item['is_valid'])
                                    <span class="text-success small fw-bold">
                                        <i class="bi bi-check-circle me-1"></i> Valid
                                    </span>
                                @else
                                    <div class="text-danger small">
                                        @foreach($item['errors'] as $error)
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                {{ $error }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No data found in the uploaded file.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .table-danger-subtle {
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
</style>
@endsection
