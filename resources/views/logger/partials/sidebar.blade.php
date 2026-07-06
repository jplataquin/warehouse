<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-uppercase small text-muted">
            <i class="bi bi-geo-alt me-1"></i> My Warehouses
        </h6>
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
            <div class="warehouse-type-group" data-type="{{ $type }}">
                <div class="bg-light px-3 py-2 small fw-bold text-secondary border-bottom border-top text-uppercase tracking-wider">
                    <i class="bi bi-folder-fill me-1 text-primary"></i> {{ $type }}
                </div>
                <div class="list-group list-group-flush">
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
        if (!searchInput) return;

        const groups = document.querySelectorAll('.warehouse-type-group');
        const items = document.querySelectorAll('.warehouse-item');
        const emptyState = document.getElementById('warehouse-sidebar-empty');

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
                } else {
                    group.classList.add('d-none');
                }
            });

            if (emptyState) {
                if (visibleTotal === 0 && items.length > 0) {
                    emptyState.classList.remove('d-none');
                } else {
                    emptyState.classList.add('d-none');
                }
            }
        });
    });
</script>
