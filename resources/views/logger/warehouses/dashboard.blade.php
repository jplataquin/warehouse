@extends('layouts.logger')

@section('inner_content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ $warehouse->name }} Dashboard</span>
        <span class="badge bg-primary">{{ $warehouse->type }}</span>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h5>Status</h5>
                <p class="text-success fw-bold">{{ $warehouse->status }}</p>
            </div>
            <div class="col-md-4">
                <h5>Project</h5>
                <p>{{ $warehouse->project ? $warehouse->project->name : 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Actions</h5>
                <a href="{{ route('ledgers.create', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-primary">New Entry</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <i class="bi bi-search me-1"></i> Check Item Stock
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-8">
                <label class="form-label small text-muted text-uppercase fw-bold">Search Item</label>
                <input type="text" id="dashboard-item-search" class="form-control" placeholder="Search item name or specification..." autocomplete="off">
            </div>
            <div class="col-md-4 text-muted small">
                <i class="bi bi-info-circle me-1"></i> Stock includes both Pending and Approved entries.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dashboard-item-search');
    const filterButtons = document.querySelectorAll('.filter-btn');
    let currentFilter = 'ALL';

    function filterItems() {
        const query = searchInput.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.item-card-wrapper');
        let visibleCount = 0;

        cards.forEach(card => {
            const itemName = card.getAttribute('data-item-name');
            const itemType = card.getAttribute('data-item-type');

            const matchesSearch = itemName.includes(query);
            const matchesFilter = currentFilter === 'ALL' || itemType === currentFilter;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const noResults = document.getElementById('no-search-results');
        if (visibleCount === 0 && (query !== '' || currentFilter !== 'ALL')) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }

    searchInput.addEventListener('input', filterItems);

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => {
                b.classList.remove('active');
                if (b.getAttribute('data-filter') === 'ALL') b.className = 'btn btn-outline-secondary filter-btn';
                if (b.getAttribute('data-filter') === 'CONSUMABLE') b.className = 'btn btn-outline-danger filter-btn';
                if (b.getAttribute('data-filter') === 'ASSET') b.className = 'btn btn-outline-success filter-btn';
                if (b.getAttribute('data-filter') === 'RECOVERABLE') b.className = 'btn btn-outline-warning text-dark filter-btn';
            });

            currentFilter = this.getAttribute('data-filter');
            if (currentFilter === 'ALL') this.className = 'btn btn-secondary active filter-btn';
            if (currentFilter === 'CONSUMABLE') this.className = 'btn btn-danger active filter-btn';
            if (currentFilter === 'ASSET') this.className = 'btn btn-success active filter-btn';
            if (currentFilter === 'RECOVERABLE') this.className = 'btn btn-warning text-dark active filter-btn';

            filterItems();
        });
    });
});
</script>

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2"></i> Current Inventory Stock</h5>
        <div class="btn-group btn-group-sm w-100 w-md-auto" role="group" aria-label="Item Type Filters">
            <button type="button" class="btn btn-secondary active filter-btn" data-filter="ALL">All</button>
            <button type="button" class="btn btn-outline-danger filter-btn" data-filter="CONSUMABLE">Consumables</button>
            <button type="button" class="btn btn-outline-success filter-btn" data-filter="ASSET">Assets</button>
            <button type="button" class="btn btn-outline-warning text-dark filter-btn" data-filter="RECOVERABLE">Recoverables</button>
        </div>
    </div>
    
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @forelse($items as $item)
            <div class="col item-card-wrapper" data-item-name="{{ strtolower($item->name) }} {{ strtolower($item->specification) }}" data-item-type="{{ $item->type }}">
                <div class="card shadow-sm border-1 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title fw-bold text-truncate mb-0" title="{{ $item->name }} {{ $item->specification }}">
                                @if($item->type === 'CONSUMABLE')
                                    <span class="badge bg-danger me-1">C</span>
                                @elseif($item->type === 'RECOVERABLE')
                                    <span class="badge bg-warning text-dark me-1">R</span>
                                @elseif($item->type === 'ASSET')
                                    <span class="badge bg-success me-1">A</span>
                                @endif
                                {{ $item->name }} {{ $item->specification }}
                            </h6>
                        </div>
                        <div class="mt-2">
                            <div class="small text-muted text-uppercase fw-bold">Stock Level</div>
                            <div class="h4 mb-0 fw-bold text-primary">
                                {{ $item->current_stock }} <span class="fs-6 text-muted fw-normal">{{ $item->unit }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                        <a href="{{ route('ledgers.index', ['warehouse_id' => $warehouse->id, 'item_id' => $item->id]) }}" class="btn btn-sm btn-link p-0 text-decoration-none small">
                            <i class="bi bi-clock-history me-1"></i> View History
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 bg-light rounded">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No items found in the system.</p>
                </div>
            </div>
        @endforelse

        <div id="no-search-results" class="col-12" style="display: none;">
            <div class="text-center py-5 bg-light rounded text-muted">
                <i class="bi bi-search fs-1 d-block mb-2"></i>
                No items found matching your search.
            </div>
        </div>
    </div>
</div>
@endsection
