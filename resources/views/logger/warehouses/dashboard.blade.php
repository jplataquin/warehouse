@extends('layouts.logger')

@section('inner_content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Dashboard</span>
        <span class="badge bg-primary">{{ $warehouse->type }}</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8 border-end mb-2">
                <h5 class="text-muted small text-uppercase fw-bold">Project</h5>
                <p class="text-dark mb-2 text-truncate" title="{{ $warehouse->project ? $warehouse->project->name : 'N/A' }}">{{ $warehouse->project ? $warehouse->project->name : 'N/A' }}</p>

                @if($warehouse->parent)
                    <div class="mt-3">
                        <h5 class="text-muted small text-uppercase fw-bold">Parent Warehouse</h5>
                        <p class="mb-0">
                            <a href="{{ route('logger.warehouse.dashboard', $warehouse->parent->id) }}" class="text-decoration-none fw-bold">
                                <i class="bi bi-arrow-up-circle-fill me-1"></i> {{ $warehouse->parent->name }}
                            </a>
                        </p>
                    </div>

                    @if($warehouse->parent->children->isNotEmpty())
                        <div class="mt-3" style="max-width: 300px;">
                            <h5 class="text-muted small text-uppercase fw-bold">Sub-Warehouses</h5>
                            <select class="form-select form-select-sm mt-1" onchange="if(this.value) window.location.href=this.value;">
                                <option value="">-- Switch Sub-Warehouse --</option>
                                @foreach($warehouse->parent->children as $child)
                                    <option value="{{ route('logger.warehouse.dashboard', $child->id) }}" {{ $warehouse->id == $child->id ? 'selected' : '' }}>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @elseif($warehouse->children->isNotEmpty())
                    <div class="mt-3" style="max-width: 300px;">
                        <h5 class="text-muted small text-uppercase fw-bold">Sub-Warehouses</h5>
                        <select class="form-select form-select-sm mt-1" onchange="if(this.value) window.location.href=this.value;">
                            <option value="">-- Select Sub-Warehouse --</option>
                            @foreach($warehouse->children as $child)
                                <option value="{{ route('logger.warehouse.dashboard', $child->id) }}">
                                    {{ $child->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="col-md-2 border-end mb-2">
                <h5 class="text-muted small text-uppercase fw-bold">Item Count</h5>
                <p class="fw-bold text-primary mb-0 fs-5">{{ $items->count() }}</p>
            </div>
            <div class="col-md-2 mb-2">
                <h5 class="text-muted small text-uppercase fw-bold">Quick Actions</h5>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('ledgers.create', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> New Entry
                    </a>
                    <a href="{{ route('logger.items.create', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-box-seam me-1"></i> Add New Item
                    </a>
                    @if($warehouse->type === 'CENTRAL' && is_null($warehouse->parent_id))
                        <a href="{{ route('logger.sub-warehouses.create', $warehouse->id) }}" class="btn btn-sm btn-outline-success w-100">
                            <i class="bi bi-diagram-3 me-1"></i> Create Sub-Wh
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <i class="bi bi-box-seam me-2"></i> Inventory
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-12">
                <input type="text" id="dashboard-item-search" class="form-control" placeholder="Search item name or specification..." autocomplete="off">
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #item-type-tabs .nav-link {
        border-radius: 8px 8px 0 0;
        margin-right: 4px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-bottom: none;
        padding: 0.5rem 1rem;
        transition: all 0.15s ease-in-out;
    }
    #item-type-tabs .nav-link:hover {
        background-color: #e9ecef;
        color: inherit;
    }
    #item-type-tabs .nav-link.active {
        background-color: #fff !important;
        border-color: #dee2e6 #dee2e6 #fff !important;
    }
    #item-type-tabs .nav-link[data-filter="CONSUMABLE"].active {
        border-top: 3px solid #dc3545 !important;
        color: #212529 !important;
    }
    #item-type-tabs .nav-link[data-filter="ASSET"].active {
        border-top: 3px solid #198754 !important;
        color: #212529 !important;
    }

    #item-type-tabs .nav-link[data-filter="ALL"].active {
        border-top: 3px solid #6c757d !important;
        color: #6c757d !important;
    }
    #item-type-tabs {
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush

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
            });

            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');

            filterItems();
        });
    });
});
</script>

<div class="mb-4">
    <div class="mb-3">
        <ul class="nav nav-tabs border-bottom-0" id="item-type-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active filter-btn text-secondary fw-bold" type="button" data-filter="ALL">All</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link filter-btn text-danger fw-bold" type="button" data-filter="CONSUMABLE">Consumable</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link filter-btn text-success fw-bold" type="button" data-filter="ASSET">Asset</button>
            </li>
            
        </ul>
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
                                
                                @elseif($item->type === 'ASSET')
                                    <span class="badge bg-success me-1">A</span>
                                @endif
                                {{ $item->name }} {{ $item->specification }}
                            </h6>
                        </div>
                        @if(isset($item->warehouse_context) && $item->warehouse_context->parent_id)
                            <div class="mb-2">
                                <span class="badge bg-light text-dark border small">
                                    <i class="bi bi-diagram-3-fill text-primary me-1"></i>{{ $item->warehouse_context->name }}
                                </span>
                            </div>
                        @endif
                        <div class="mt-2">
                            <div class="small text-muted text-uppercase fw-bold">Stock Level</div>
                            <div class="h4 mb-0 fw-bold text-primary">

                                @php 
                                    $number         = $item->current_stock;
                                    $decimals       = strlen(substr(strrchr($number, "."), 1));
                                    $current_stock  = number_format($number, $decimals, '.', ',');
                                @endphp
                                {{ $current_stock }} <span class="fs-6 text-muted fw-normal">{{ $item->unit }}</span>
                            </div>
                        </div>
                        @if($item->type === 'ASSET')
                        <div class="mt-2">
                           
                            @if($item->status === 'Out of Order')
                                <span class="badge bg-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i> Out of Order</span>
                            @else
                                <span class="badge bg-success small"><i class="bi bi-check-circle-fill me-1"></i> Operational</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                        <a href="{{ route('ledgers.index', ['warehouse_id' => $item->warehouse_context->id ?? $warehouse->id, 'item_id' => $item->id]) }}" class="btn btn-sm btn-link p-0 text-decoration-none small">
                            <i class="bi bi-clock-history me-1"></i> View Ledger
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
