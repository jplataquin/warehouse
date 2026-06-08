@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-eye me-2"></i> Preview Projects Import</h5>
            <a href="{{ route('projects.import.form') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Upload
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Row</th>
                            <th>Project Name</th>
                            <th>Create Site WH</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewData as $item)
                        <tr class="{{ !$item['is_valid'] ? 'table-danger-subtle' : '' }}">
                            <td class="ps-4 text-muted small">{{ $item['row_number'] }}</td>
                            <td>
                                <div class="fw-bold">{{ $item['name'] }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $item['create_warehouse'] ? 'bg-info' : 'bg-light text-dark border' }}">
                                    {{ $item['create_warehouse'] ? 'YES' : 'NO' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item['is_valid'])
                                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Ready</span>
                                @else
                                    @foreach($item['errors'] as $error)
                                        <div class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> {{ $error }}</div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            @php
                $validCount = count(array_filter($previewData, fn($i) => $i['is_valid']));
                $totalCount = count($previewData);
            @endphp
            <div class="small text-muted">
                Showing <strong>{{ $totalCount }}</strong> rows. <strong>{{ $validCount }}</strong> projects ready to import.
            </div>
            <form action="{{ route('projects.import.store') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary px-4" {{ $validCount === 0 ? 'disabled' : '' }}>
                    Confirm and Import
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
