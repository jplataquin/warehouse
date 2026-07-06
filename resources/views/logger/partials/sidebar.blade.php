@push('styles')
<style>
    .accordion-toggle-btn {
        transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
    }
    .accordion-toggle-btn:hover {
        background-color: #e2e6ea !important;
        color: #212529 !important;
    }
</style>
@endpush

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-uppercase small text-muted">
            <i class="bi bi-geo-alt me-1"></i> My Warehouses
        </h6>
        @if(!$warehouses->isEmpty())
            <button id="btn-close-all-warehouses" class="btn btn-sm btn-outline-secondary py-1 px-2 border-0" style="font-size: 0.75rem; font-weight: 600;" type="button">
                <i class="bi bi-chevron-bar-contract"></i> Close All
            </button>
        @endif
    </div>

    @if(!$warehouses->isEmpty())
        <div class="p-2 border-bottom bg-light">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="warehouse-sidebar-search" class="form-control border-start-0" placeholder="Search warehouse...">
            </div>
        </div>
    @endif

    <div id="warehouse-sidebar-groups">
        @php
            $groupedWarehouses = $warehouses->groupBy('type');
        @endphp

        @forelse($groupedWarehouses as $type => $whs)
            @php
                $hasActiveWarehouse = false;
                foreach($whs as $w) {
                    if (isset($warehouse) && (int)$warehouse->id === (int)$w->id) {
                        $hasActiveWarehouse = true;
                        break;
                    }
                }
            @endphp
            <div class="warehouse-type-group" data-type="{{ $type }}" data-has-active="{{ $hasActiveWarehouse ? 'true' : 'false' }}">
                <div class="accordion-header">
                    <button class="{{ $hasActiveWarehouse ? 'bg-light text-secondary' : 'bg-light text-secondary accordion-toggle-btn' }} px-3 py-2 small fw-bold border-bottom border-top text-uppercase tracking-wider d-flex justify-content-between align-items-center w-100 border-0" 
                            style="text-align: left; outline: none; box-shadow: none; cursor: {{ $hasActiveWarehouse ? 'default' : 'pointer' }};"
                            type="button">
                        <span>
                            <i class="bi bi-folder-fill me-1 text-primary"></i> {{ $type }}
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            @if($hasActiveWarehouse)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.65rem; font-weight: 700;">
                                    <i class="bi bi-pin-angle-fill me-1"></i> ACTIVE
                                </span>
                            @endif
                            <i class="bi bi-chevron-down accordion-icon" style="font-size: 0.75rem; transition: transform 0.2s ease-in-out; transform: rotate({{ $hasActiveWarehouse ? '180' : '0' }}deg);"></i>
                        </div>
                    </button>
                </div>
                <div class="accordion-collapse {{ $hasActiveWarehouse ? '' : 'd-none' }}">
                    <div class="list-group list-group-flush border-bottom">
                        @foreach($whs as $w)
                            @php
                                $isActive = isset($warehouse) && (int)$warehouse->id === (int)$w->id;
                            @endphp
                            <a href="{{ route('logger.warehouse.dashboard', $w) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center warehouse-item {{ $isActive ? 'active bg-primary-subtle border-start border-primary border-4 text-primary fw-bold' : '' }}"
                               data-name="{{ strtolower($w->name) }}">
                                <div class="text-truncate" style="max-width: 80%;">
                                    {{ $w->name }}
                                </div>
                                @if($isActive)
                                    <span class="badge bg-primary small">ACTIVE</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-4">
                No warehouses assigned
            </div>
        @endforelse

        <div id="warehouse-sidebar-empty" class="list-group-item text-muted text-center py-4 d-none">
            No matching warehouses found
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="list-group list-group-flush rounded">
        <a href="{{ route('logger.rules') }}" class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('logger.rules') ? 'active bg-primary text-white fw-bold' : 'text-primary' }}">
            <i class="bi bi-info-circle-fill me-2"></i> Movement Rules Guide
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('warehouse-sidebar-search');
        const closeAllBtn = document.getElementById('btn-close-all-warehouses');
        const groups = document.querySelectorAll('.warehouse-type-group');
        const items = document.querySelectorAll('.warehouse-item');
        const emptyState = document.getElementById('warehouse-sidebar-empty');

        // Toggle accordion on click
        groups.forEach(group => {
            const button = group.querySelector('.accordion-header button');
            if (!button) return;

            button.addEventListener('click', function() {
                const hasActive = group.getAttribute('data-has-active') === 'true';
                if (hasActive) {
                    // Accordion with active selected warehouse should not be closable
                    return;
                }

                const collapseDiv = group.querySelector('.accordion-collapse');
                const icon = group.querySelector('.accordion-icon');
                if (collapseDiv) {
                    if (collapseDiv.classList.contains('d-none')) {
                        collapseDiv.classList.remove('d-none');
                        if (icon) icon.style.transform = 'rotate(180deg)';
                    } else {
                        collapseDiv.classList.add('d-none');
                        if (icon) icon.style.transform = 'rotate(0deg)';
                    }
                }
            });
        });

        // Search filter logic
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                let visibleTotal = 0;

                groups.forEach(group => {
                    const groupItems = group.querySelectorAll('.warehouse-item');
                    let visibleInGroup = 0;

                    groupItems.forEach(item => {
                        const name = item.getAttribute('data-name');
                        if (name.includes(query)) {
                            item.classList.remove('d-none');
                            visibleInGroup++;
                        } else {
                            item.classList.add('d-none');
                        }
                    });

                    if (visibleInGroup > 0) {
                        group.classList.remove('d-none');
                        visibleTotal += visibleInGroup;

                        // When a user is using the search filter open all accordion
                        if (query.length > 0) {
                            const collapseDiv = group.querySelector('.accordion-collapse');
                            if (collapseDiv) {
                                collapseDiv.classList.remove('d-none');
                            }
                            const icon = group.querySelector('.accordion-icon');
                            if (icon) {
                                icon.style.transform = 'rotate(180deg)';
                            }
                        }
                    } else {
                        group.classList.add('d-none');
                    }
                });

                // Revert accordions to default when search query is cleared
                if (query.length === 0) {
                    groups.forEach(group => {
                        // Restore visibility of all items
                        const groupItems = group.querySelectorAll('.warehouse-item');
                        groupItems.forEach(item => item.classList.remove('d-none'));
                        group.classList.remove('d-none');

                        const hasActive = group.getAttribute('data-has-active') === 'true';
                        const collapseDiv = group.querySelector('.accordion-collapse');
                        const icon = group.querySelector('.accordion-icon');
                        if (hasActive) {
                            if (collapseDiv) collapseDiv.classList.remove('d-none');
                            if (icon) icon.style.transform = 'rotate(180deg)';
                        } else {
                            if (collapseDiv) collapseDiv.classList.add('d-none');
                            if (icon) icon.style.transform = 'rotate(0deg)';
                        }
                    });
                }

                if (emptyState) {
                    if (visibleTotal === 0 && items.length > 0) {
                        emptyState.classList.remove('d-none');
                    } else {
                        emptyState.classList.add('d-none');
                    }
                }
            });
        }

        // Close all accordion button logic
        if (closeAllBtn) {
            closeAllBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                }
                
                groups.forEach(group => {
                    // Show all items and group
                    const groupItems = group.querySelectorAll('.warehouse-item');
                    groupItems.forEach(item => item.classList.remove('d-none'));
                    group.classList.remove('d-none');

                    const hasActive = group.getAttribute('data-has-active') === 'true';
                    const collapseDiv = group.querySelector('.accordion-collapse');
                    const icon = group.querySelector('.accordion-icon');
                    if (hasActive) {
                        // Active selected warehouse should NOT be closable
                        if (collapseDiv) collapseDiv.classList.remove('d-none');
                        if (icon) icon.style.transform = 'rotate(180deg)';
                    } else {
                        if (collapseDiv) collapseDiv.classList.add('d-none');
                        if (icon) icon.style.transform = 'rotate(0deg)';
                    }
                });

                if (emptyState) {
                    emptyState.classList.add('d-none');
                }
            });
        }
    });
</script>