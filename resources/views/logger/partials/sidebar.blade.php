<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-uppercase small text-muted">
            <i class="bi bi-geo-alt me-1"></i> My Warehouses
        </h6>
    </div>
    <div class="list-group list-group-flush">
        @forelse($warehouses as $w)
            @php
                $isActive = isset($warehouse) && (int)$warehouse->id === (int)$w->id;
            @endphp
            <a href="{{ route('logger.warehouse.dashboard', $w) }}" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $isActive ? 'active bg-primary-subtle border-start border-primary border-4 text-primary fw-bold' : '' }}">
                <div>
                    {{ $w->name }}
                </div>
                @if($isActive)
                    <span class="badge bg-primary small">ACTIVE</span>
                @endif
            </a>
        @empty
            <div class="list-group-item text-muted text-center py-4">
                No warehouses assigned
            </div>
        @endforelse
    </div>
</div>
