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
                <input type="text" id="dashboard-item-search" class="form-control" placeholder="Type item name..." list="item-options" autocomplete="off">
            </div>
            <div class="col-md-4 text-muted small">
                <i class="bi bi-info-circle me-1"></i> Stock includes both Pending and Approved entries.
            </div>
        </div>
    </div>
</div>

{{-- Fixed Footer Stock Bar --}}
<div id="fixed-stock-footer" class="fixed-bottom bg-dark text-white py-2 shadow-lg border-top border-primary border-3" style="display: none; z-index: 1030;">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="bg-primary p-2 rounded-circle me-3">
                    <i class="bi bi-stack fs-4 text-white"></i>
                </div>
                <div>
                    <div class="small text-muted text-uppercase fw-bold">Currently in Stock</div>
                    <div class="h4 mb-0 fw-bold" id="stock-display-combined">
                        <span id="stock-value">0</span>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted text-uppercase fw-bold">Selected Item</div>
                <div class="fw-bold text-info" id="footer-item-name"></div>
            </div>
        </div>
    </div>
</div>

<datalist id="item-options">
    @foreach($items as $item)
        <option value="{{ $item->name }} {{ $item->specification }} {{ $item->unit }} ({{ $item->type }})" data-id="{{ $item->id }}"></option>
    @endforeach
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dashboard-item-search');
    const datalist = document.getElementById('item-options');
    const stockFooter = document.getElementById('fixed-stock-footer');
    const footerItemName = document.getElementById('footer-item-name');
    const stockValue = document.getElementById('stock-value');
    const stockUnit = document.getElementById('stock-unit');
    const warehouseId = @json($warehouse->id);

    searchInput.addEventListener('input', async function() {
        const val = this.value;
        const options = datalist.options;
        let itemId = null;
        let itemName = null;

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === val) {
                itemId = options[i].getAttribute('data-id');
                itemName = val;
                break;
            }
        }

        if (itemId) {
            stockValue.textContent = '...';
            footerItemName.textContent = itemName;
            stockFooter.style.display = 'block';
            document.body.style.paddingBottom = '80px';

            try {
                const response = await fetch(`{{ url('items') }}/${itemId}/stock?warehouse_id=${warehouseId}`);
                const data = await response.json();
                stockValue.textContent = data.balance + ' ' + data.unit;
            } catch (error) {
                console.error('Error fetching stock:', error);
                stockValue.textContent = 'Error';
            }
        } else {
            if (val === '') {
                stockFooter.style.display = 'none';
                document.body.style.paddingBottom = '0';
            }
        }
    });
});
</script>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2"></i> Current Inventory Stock</h5>
    </div>
    
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
        @forelse($items as $item)
            <div class="col">
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
    </div>
</div>
@endsection
