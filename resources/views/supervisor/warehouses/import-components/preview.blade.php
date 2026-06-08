@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">MQMS Component Import Preview</h5>
                    <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 bg-light border-bottom">
                        <p class="mb-0 text-muted small">
                            Importing components for <strong>{{ $warehouse->name }}</strong>. 
                            Approved components fetched from MQMS are listed below. 
                            Select the ones you want to add as allocations.
                        </p>
                    </div>

                    <form action="{{ route('warehouses.import-components.store', $warehouse) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th width="40" class="ps-4">
                                            <input type="checkbox" id="select-all" class="form-check-input">
                                        </th>
                                        <th>Component Name</th>
                                        <th>MQMS ID</th>
                                        <th>Status/Errors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($previewData as $index => $item)
                                        <tr class="{{ !$item['is_valid'] ? 'table-light text-muted' : '' }}">
                                            <td class="ps-4">
                                                @if($item['is_valid'])
                                                    <input type="checkbox" name="selected_components[{{ $index }}][id]" value="{{ $item['id'] }}" class="form-check-input component-checkbox">
                                                    <input type="hidden" name="selected_components[{{ $index }}][name]" value="{{ $item['name'] }}">
                                                @else
                                                    <input type="checkbox" class="form-check-input" disabled>
                                                @endif
                                            </td>
                                            <td class="fw-bold">{{ $item['name'] }}</td>
                                            <td><code class="small">{{ $item['id'] }}</code></td>
                                            <td>
                                                @if($item['is_valid'])
                                                    <span class="badge bg-success">Ready to Import</span>
                                                @else
                                                    @foreach($item['errors'] as $error)
                                                        <div class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> {{ $error }}</div>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                No approved components found for the selected section.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white py-3 text-end">
                            <button type="submit" class="btn btn-primary px-4" id="import-btn" disabled>
                                <i class="bi bi-cloud-download me-1"></i> Import Selected Components
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.component-checkbox');
    const importBtn = document.getElementById('import-btn');

    function updateImportBtn() {
        const checkedCount = document.querySelectorAll('.component-checkbox:checked').length;
        importBtn.disabled = checkedCount === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateImportBtn();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateImportBtn);
    });
});
</script>
@endsection
