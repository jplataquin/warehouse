@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">Review Pending Items</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-danger rounded-pill px-3 py-2">
                    {{ $items->total() }} Pending Review
                </span>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.items.review') }}" method="GET" class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search item name or specification..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="CONSUMABLE" {{ request('type') === 'CONSUMABLE' ? 'selected' : '' }}>Consumable</option>
                        <option value="ASSET" {{ request('type') === 'ASSET' ? 'selected' : '' }}>Asset</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Details</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i> Pending Review
                                    </span>
                                </div>
                                <div class="small text-muted">{{ $item->specification }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->type }}</span>
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info toggle-similar" data-bs-toggle="collapse" data-bs-target="#similar-collapse-{{ $item->id }}" data-item-id="{{ $item->id }}" data-url="{{ route('items.similar', $item) }}" title="Show Similar Items">
                                        <i class="bi bi-layers-half me-1"></i> Similar
                                    </button>
                                    <form action="{{ route('items.approve', $item) }}" method="POST" class="d-inline approve-form" data-item-id="{{ $item->id }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve Item">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                    </form>
                                    <a href="{{ route('items.edit', array_merge(['item' => $item->id], request()->query())) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                    <a href="{{ route('items.merge.form', $item) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-arrow-left-right me-1"></i> Merge
                                    </a>
                                    <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item? This action will hide the item from future logs, though historical ledger movements will remain intact.');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="similar-row-container d-none" id="similar-row-{{ $item->id }}">
                            <td colspan="4" class="p-0 border-0">
                                <div class="collapse similar-collapse" id="similar-collapse-{{ $item->id }}" data-item-id="{{ $item->id }}" data-url="{{ route('items.similar', $item) }}">
                                    <div class="p-3 border-top border-bottom bg-light bg-opacity-50">
                                        <div class="d-flex justify-content-center py-3 similar-loading">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <span class="ms-2 text-muted small">Searching for similar items...</span>
                                        </div>
                                        <div class="similar-content"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No pending items found matching the criteria. All items are reviewed!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const collapsibles = document.querySelectorAll('.similar-collapse');

    collapsibles.forEach(collapse => {
        const itemId = collapse.dataset.itemId;
        const rowContainer = document.getElementById(`similar-row-${itemId}`);

        // Listen for when the accordion starts to expand
        collapse.addEventListener('show.bs.collapse', function () {
            rowContainer.classList.remove('d-none');
            
            const contentDiv = collapse.querySelector('.similar-content');
            const loadingDiv = collapse.querySelector('.similar-loading');

            // Only fetch if we haven't already fetched it
            if (contentDiv.innerHTML.trim() === '') {
                const url = collapse.dataset.url;
                
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        loadingDiv.classList.remove('d-flex');
                        loadingDiv.classList.add('d-none');
                        contentDiv.innerHTML = html;
                    })
                    .catch(err => {
                        loadingDiv.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Failed to load similar items: ${err.message}</span>`;
                        console.error('Error fetching similar items:', err);
                    });
            }
        });

        // Listen for when the accordion is fully hidden
        collapse.addEventListener('hidden.bs.collapse', function () {
            rowContainer.classList.add('d-none');
        });
    });

    // Intercept approval form submissions
    const approveForms = document.querySelectorAll('.approve-form');
    approveForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Approving...`;
            
            const itemId = form.dataset.itemId;
            const url = form.getAttribute('action');
            const token = form.querySelector('input[name="_token"]').value;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find rows to remove
                    const mainRow = form.closest('tr');
                    const similarRow = document.getElementById(`similar-row-${itemId}`);
                    
                    // Fade out and remove rows
                    mainRow.style.transition = 'all 0.5s ease';
                    mainRow.style.opacity = '0';
                    mainRow.style.transform = 'translateX(-20px)';
                    
                    if (similarRow) {
                        similarRow.style.transition = 'all 0.5s ease';
                        similarRow.style.opacity = '0';
                    }
                    
                    setTimeout(() => {
                        mainRow.remove();
                        if (similarRow) similarRow.remove();
                        
                        // If no more rows are left on the page, reload or show empty state
                        const remainingRows = document.querySelectorAll('table tbody tr:not(.similar-row-container)');
                        if (remainingRows.length === 0) {
                            location.reload(); // reload to show empty state/pagination recalculation
                        }
                    }, 500);
                    
                    // Dynamic Badge Updates!
                    const pendingBadges = document.querySelectorAll('.nav-pill, .badge.bg-danger, .badge.bg-danger.rounded-pill');
                    pendingBadges.forEach(badge => {
                        let countText = badge.textContent.trim().replace(' Pending Review', '');
                        let count = parseInt(countText);
                        if (!isNaN(count) && count > 0) {
                            count--;
                            badge.innerHTML = badge.className.includes('nav-pill') 
                                ? `${count} Pending Review` 
                                : count;
                            if (count === 0 && !badge.className.includes('nav-pill')) {
                                badge.className = 'badge bg-secondary ms-1';
                            }
                        }
                    });
                } else {
                    alert('Failed to approve item: ' + (data.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(err => {
                alert('An error occurred: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    });
});
</script>
@endsection