@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <i class="bi bi-arrow-left-right me-2 fs-5"></i>
                    <h5 class="mb-0 fw-bold">Admin Console: Merge & Consolidate Items</h5>
                </div>
                <div class="card-body p-4">
                    <!-- Global Error Display -->
                    @if ($errors->any())
                        <div class="alert alert-danger shadow-sm border-0 mb-4">
                            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</h6>
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-warning border-0 shadow-sm mb-4 bg-warning bg-opacity-10 text-dark">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold text-uppercase tracking-wider text-warning">Irreversible Action Warning</h6>
                                <p class="mb-0 small text-muted">
                                    Merging items is a permanent action. All historical and current records referencing the <strong>Source Item</strong> will be permanently re-assigned to the <strong>Target Item</strong> in both the <strong>Ledger</strong> and <strong>Asset Utilization</strong> tables. The Source Item will then be soft-deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Source Item Info Box -->
                    <div class="card bg-light border-0 mb-4 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-uppercase fw-bold text-danger mb-3 small tracking-wider">
                                <i class="bi bi-box-arrow-right me-1"></i> Source Item (To Be Deleted)
                            </h6>
                            <div class="row align-items-center">
                                <div class="col-sm-8">
                                    <div class="h5 fw-bold text-dark mb-1">{{ $item->name }}</div>
                                    <div class="text-muted small mb-0">
                                        <strong>Spec:</strong> {{ $item->specification ?? 'None' }} | 
                                        <strong>Unit:</strong> {{ $item->unit }} | 
                                        <strong>Type:</strong> <span class="badge bg-secondary bg-opacity-10 text-dark">{{ $item->type }}</span>
                                    </div>
                                </div>
                                <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                                    <span class="badge bg-danger text-white py-2 px-3 fs-6 rounded-pill">
                                        ID: #{{ $item->id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Record Notification Block -->
                    <div class="card border-0 bg-info bg-opacity-10 mb-4 shadow-sm text-info-emphasis">
                        <div class="card-body d-flex align-items-center">
                            <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Impact Analysis</h6>
                                <p class="mb-0 small">
                                    There are currently <strong>{{ $ledgerCount }}</strong> ledger records associated with this item that will be redirected.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('items.merge', $item) }}" method="POST" id="merge-form">
                        @csrf

                        <!-- Target Item Search & Select -->
                        <div class="mb-4 position-relative" id="autocomplete-container">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="bi bi-box-arrow-in-left text-success me-1"></i> Select Target Item (To Keep)
                            </label>
                            
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-muted" id="search-icon"></i>
                                    <div class="spinner-border spinner-border-sm text-primary" id="search-spinner" style="display: none;" role="status"></div>
                                </span>
                                <input type="text" id="target-item-search" class="form-control form-control-lg border-start-0 border-opacity-50" placeholder="Type name, ID, or specification to search target..." autocomplete="off" required>
                            </div>
                            <input type="hidden" name="target_item_id" id="target-item-id" value="{{ old('target_item_id') }}">
                            
                            <!-- CUSTOM AUTOCOMPLETE DROPDOWN OVERLAY -->
                            <div id="search-results-dropdown" class="dropdown-menu shadow-lg w-100 p-0 overflow-auto" style="max-height: 280px; display: none; position: absolute; z-index: 1050; top: 100%; left: 0; border: 1px solid rgba(0,0,0,0.15);">
                                <!-- Results will be injected here dynamically -->
                            </div>
                            
                            <div class="form-text small text-muted mt-2">
                                Search and select the exact target item to merge into. The item ID is shown in the search list to ensure precision.
                            </div>
                        </div>

                        <!-- IRREVERSIBLE CONFIRMATION CHECKBOX -->
                        <div class="mb-4">
                            <div class="form-check p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded shadow-sm">
                                <input class="form-check-input ms-1 me-2" type="checkbox" name="confirm_merge" id="confirm_merge" value="1" required>
                                <label class="form-check-label text-danger fw-bold small" for="confirm_merge">
                                    I confirm that this merge is correct, and I understand that this consolidation is irreversible.
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg px-4" id="submit-btn" disabled>
                                <i class="bi bi-arrow-left-right me-1"></i> Consolidate & Merge Items
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
    const itemSearch = document.getElementById('target-item-search');
    const itemIdHidden = document.getElementById('target-item-id');
    const resultsDropdown = document.getElementById('search-results-dropdown');
    const confirmCheckbox = document.getElementById('confirm_merge');
    const submitBtn = document.getElementById('submit-btn');
    const searchIcon = document.getElementById('search-icon');
    const searchSpinner = document.getElementById('search-spinner');

    let debounceTimeout = null;

    function checkFormValidity() {
        const hasValidTarget = itemIdHidden.value !== '';
        const isConfirmed = confirmCheckbox.checked;
        submitBtn.disabled = !(hasValidTarget && isConfirmed);
    }

    itemSearch.addEventListener('input', function() {
        const query = this.value.trim();
        itemIdHidden.value = ''; // Reset ID on type
        checkFormValidity();

        clearTimeout(debounceTimeout);

        if (query.length < 1) {
            resultsDropdown.innerHTML = '';
            resultsDropdown.style.display = 'none';
            return;
        }

        searchIcon.style.display = 'none';
        searchSpinner.style.display = 'inline-block';

        debounceTimeout = setTimeout(() => {
            fetch(`{{ route('items.merge.search', $item) }}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    searchSpinner.style.display = 'none';
                    searchIcon.style.display = 'inline-block';

                    resultsDropdown.innerHTML = '';
                    if (data.length === 0) {
                        resultsDropdown.innerHTML = '<div class="p-3 text-muted text-center small"><i class="bi bi-info-circle me-1"></i>No matching items found</div>';
                        resultsDropdown.style.display = 'block';
                        return;
                    }

                    data.forEach(target => {
                        const specStr = target.specification ? ` (${target.specification})` : '';
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'dropdown-item text-wrap py-2 border-bottom border-light text-start w-100';
                        button.style.outline = 'none';
                        button.innerHTML = `
                            <div class="fw-bold text-dark small">${target.name}${specStr}</div>
                            <div class="text-muted extra-small d-flex align-items-center gap-1 mt-1" style="font-size: 0.75rem;">
                                <span>ID: <strong class="text-primary">#${target.id}</strong></span> | 
                                <span>Unit: ${target.unit}</span> | 
                                <span>Type: <span class="badge bg-secondary bg-opacity-10 text-dark extra-small">${target.type}</span></span>
                            </div>
                        `;
                        
                        button.addEventListener('click', function() {
                            itemSearch.value = `ID: ${target.id} - ${target.name}${specStr} - ${target.unit}`;
                            itemIdHidden.value = target.id;
                            resultsDropdown.innerHTML = '';
                            resultsDropdown.style.display = 'none';
                            checkFormValidity();
                        });

                        resultsDropdown.appendChild(button);
                    });

                    resultsDropdown.style.display = 'block';
                })
                .catch(err => {
                    searchSpinner.style.display = 'none';
                    searchIcon.style.display = 'inline-block';
                    console.error('Error fetching merge targets:', err);
                });
        }, 250); // 250ms debounce
    });

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!document.getElementById('autocomplete-container').contains(e.target)) {
            resultsDropdown.style.display = 'none';
        }
    });

    confirmCheckbox.addEventListener('change', checkFormValidity);
});
</script>
@endsection
