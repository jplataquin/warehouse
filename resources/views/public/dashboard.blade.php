@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i> {{ $warehouse->name }} Public Stock Dashboard</h5>
            <span class="badge bg-light text-primary fw-bold">{{ $warehouse->type }}</span>
        </div>
        <div class="card-body py-4">
            <div class="row text-center">
                <div class="col-md-12 border-end">
                    <h6 class="text-muted text-uppercase fw-bold small">Status</h6>
                    <p class="text-success fw-bold fs-5 mb-0">{{ $warehouse->status }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-search me-2"></i> Check Item Stock</h6>
        </div>
        <div class="card-body py-3">
            <div class="row align-items-end g-3">
                <div class="col-md-12">
                    <label class="form-label small text-muted text-uppercase fw-bold">Search Item</label>
                    <input type="text" id="dashboard-item-search" class="form-control" placeholder="Type item name..." list="item-options" autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    {{-- Fixed Footer Stock Bar --}}
    <div id="fixed-stock-footer" class="fixed-bottom bg-dark text-white py-2 shadow-lg border-top border-primary border-3" style="display: none; z-index: 1030;">
        <div class="container">
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

    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2"></i> Current Inventory Stock</h5>
        
        <div class="row row-cols-1 row-cols-xs-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            @forelse($items as $item)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0" style="border: 1px solid #e3e6f0 !important;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title fw-bold text-truncate mb-0" title="{{ $item->name }} {{ $item->specification }} {{ $item->unit }} ({{ $item->type }})">
                                        {{ $item->name }}
                                    </h6>
                                </div>
                                @if($item->specification)
                                    <div class="text-muted small mb-2 text-truncate">{{ $item->specification }}</div>
                                @else
                                    <div class="text-muted small mb-2 text-truncate" style="visibility: hidden;">No Specification</div>
                                @endif
                                <div class="small text-muted text-uppercase fw-bold">Unit: {{ $item->unit }}</div>
                            </div>
                            <div class="mt-3">
                                <div class="small text-muted text-uppercase fw-bold">Stock Level</div>
                                <div class="h4 mb-0 fw-bold text-primary">
                                    {{ $item->current_stock }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 bg-light rounded shadow-sm">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No inventory in stock at this warehouse.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dashboard-item-search');
    const datalist = document.getElementById('item-options');
    const stockFooter = document.getElementById('fixed-stock-footer');
    const footerItemName = document.getElementById('footer-item-name');
    const stockValue = document.getElementById('stock-value');
    const token = @json($warehouse->public_token);

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
                const response = await fetch(`{{ url('public/items') }}/${itemId}/stock?token=${token}`);
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
@endsection
