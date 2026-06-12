@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Import Projects from MQMS (Preview)</span>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i> 
                Below is a list of <strong>ACTV</strong> projects fetched from the MQMS API. Select the projects you wish to import.
            </div>

            <form action="{{ route('projects.mqms-import.store') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all" class="form-check-input border border-primary">
                                </th>
                                <th>MQMS ID</th>
                                <th>Project Name</th>
                                <th>Auto Create Site WH</th>
                                <th>Status / Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($previewData as $index => $item)
                            <tr class="{{ !$item['is_valid'] ? 'table-light text-muted' : '' }}">
                                <td>
                                    <input type="checkbox" name="selected_projects[{{ $index }}][id]" value="{{ $item['id'] }}" 
                                           class="form-check-input row-checkbox border border-primary" {{ !$item['is_valid'] ? 'disabled' : '' }}>
                                    <input type="hidden" name="selected_projects[{{ $index }}][name]" value="{{ $item['name'] }}">
                                </td>
                                <td class="font-monospace small">{{ $item['id'] }}</td>
                                <td class="fw-bold">{{ $item['name'] }}</td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" name="selected_projects[{{ $index }}][create_warehouse]" value="1" 
                                               class="form-check-input border border-primary" checked {{ !$item['is_valid'] ? 'disabled' : '' }}>
                                        <label class="form-check-label small text-muted">Site Warehouse</label>
                                    </div>
                                </td>
                                <td>
                                    @if($item['is_valid'])
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i> Ready to Import
                                        </span>
                                    @else
                                        @foreach($item['errors'] as $error)
                                            <div class="text-danger small">
                                                <i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No active projects found in MQMS to import.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary" {{ empty(array_filter($previewData, fn($i) => $i['is_valid'])) ? 'disabled' : '' }}>
                        <i class="bi bi-download me-1"></i> Import Selected Projects
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.row-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = this.checked;
                }
            });
        });
    }
});
</script>
@endsection
