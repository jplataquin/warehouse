@extends('layouts.logger')

@push('styles')
    <!-- Tom Select CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@endpush

@section('inner_content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-pencil-square fs-2 text-warning"></i>
                    </div>
                    <div>
                        <h1 class="fw-bold mb-0 text-dark">Edit Ledger Entry</h1>
                        <div class="text-muted small text-uppercase fw-bold tracking-wider">
                            ID: #{{ $ledger->id }} | Current Status: {{ $ledger->status }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('ledgers.show', $ledger) }}" class="btn btn-outline-secondary shadow-sm">
                    <i class="bi bi-arrow-left"></i> Back to Details
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('ledgers.update', $ledger) }}" id="ledger-edit-form">
                @csrf
                @method('PUT')

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Movement Details</h5>
                    </div>
                    <div class="card-body p-4 bg-light bg-opacity-50">
                        <div class="row g-3">
                            {{-- Item Selection --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Item</label>
                                <select name="item_id" id="item_id" class="form-select shadow-sm" required>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-type="{{ $item->type }}" data-unit="{{ $item->unit }}" {{ $ledger->item_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} {{ $item->specification ? ' ' . $item->specification : '' }} ({{ $item->type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Warehouse Selection --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Warehouse</label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select shadow-sm" required>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" data-is-central="{{ $wh->type !== 'SITE' ? 'true' : 'false' }}" {{ $ledger->warehouse_id == $wh->id ? 'selected' : '' }}>
                                            @if($wh->parent)
                                                {{ $wh->parent->name }} &gt; {{ $wh->name }}
                                            @else
                                                {{ $wh->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Entry Date --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control shadow-sm" value="{{ $ledger->entry_date ? $ledger->entry_date->format('Y-m-d') : date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            </div>

                            {{-- Movement Type --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Movement Type</label>
                                <select name="type" id="type" class="form-select shadow-sm" required>
                                    <option value="IN" {{ $ledger->type === 'IN' ? 'selected' : '' }}>Log IN (Entry)</option>
                                    <option value="OUT" {{ $ledger->type === 'OUT' ? 'selected' : '' }}>Log OUT (Withdrawal)</option>
                                </select>
                            </div>

                            {{-- Action --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Action</label>
                                <select name="action" id="action" class="form-select shadow-sm" required>
                                    {{-- Will be populated dynamically by JS --}}
                                </select>
                            </div>

                            {{-- Quantity --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Quantity</label>
                                <div class="input-group shadow-sm">
                                    <input type="number" step="0.01" name="quantity" id="quantity" class="form-control" value="{{ $ledger->quantity }}" required min="0.01">
                                    <span class="input-group-text text-muted small px-2" id="unit-display">Units</span>
                                </div>
                            </div>
                        </div>

                        {{-- Conditional Fields --}}
                        <div class="row g-3 mt-1">
                            {{-- IN TRANSFER: Source --}}
                            <div class="col-md-6" id="in-transfer-fields" style="display: none;">
                                <label class="form-label small fw-bold text-uppercase text-muted">Transfer Source (FROM)</label>
                                <select name="source_warehouse_id" id="source_warehouse_id" class="form-select shadow-sm border-success border-opacity-25">
                                    <option value="">Select Source Warehouse...</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $ledger->source_warehouse_id == $wh->id ? 'selected' : '' }}>
                                            @if($wh->parent)
                                                {{ $wh->parent->name }} &gt; {{ $wh->name }} (Sub WH)
                                            @else
                                                {{ $wh->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- OUT TRANSFER: Destination --}}
                            <div class="col-md-6" id="out-transfer-fields" style="display: none;">
                                <label class="form-label small fw-bold text-uppercase text-muted">Transfer Destination (TO)</label>
                                <select name="destination_warehouse_id" id="destination_warehouse_id" class="form-select shadow-sm border-danger border-opacity-25 dest-warehouse-select">
                                    <option value="">Select Destination Warehouse...</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $ledger->destination_warehouse_id == $wh->id ? 'selected' : '' }}>
                                            @if($wh->parent)
                                                {{ $wh->parent->name }} &gt; {{ $wh->name }} (Sub WH)
                                            @else
                                                {{ $wh->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- OUT ALLOCATE: Allocation --}}
                            <div class="col-md-6" id="out-allocate-fields" style="display: none;">
                                <label class="form-label small fw-bold text-uppercase text-muted">Allocation Target</label>
                                <select name="allocation_id" id="allocation_id" class="form-select shadow-sm border-danger border-opacity-25">
                                    <option value="">Select Target Allocation...</option>
                                    @foreach($allocations as $allocation)
                                        <option value="{{ $allocation->id }}" {{ $ledger->allocation_id == $allocation->id ? 'selected' : '' }}>{{ $allocation->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Project --}}
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase text-muted">Project (Optional)</label>
                                <select name="project_id" id="project_id" class="form-select shadow-sm">
                                    <option value="">Select Project...</option>
                                    @foreach($projects as $p)
                                        <option value="{{ $p->id }}" {{ $ledger->project_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Receipt Fields --}}
                        <div class="row g-3 mt-1" id="receipt-row" style="display: none;">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">PO Number <span class="required-asterisk text-danger" style="display: none;">*</span></label>
                                <input type="text" name="po_number" id="po_number" class="form-control shadow-sm receipt-input" value="{{ $ledger->po_number }}" placeholder="Optional">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">Delivery Receipt <span class="required-asterisk text-danger" style="display: none;">*</span></label>
                                <input type="text" name="delivery_receipt" id="delivery_receipt" class="form-control shadow-sm receipt-input" value="{{ $ledger->delivery_receipt }}" placeholder="Optional">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">Official Receipt</label>
                                <input type="text" name="offical_receipt" id="offical_receipt" class="form-control shadow-sm receipt-input" value="{{ $ledger->offical_receipt }}" placeholder="Optional">
                            </div>
                        </div>

                        {{-- Assigned & Plate No --}}
                        <div class="row g-3 mt-1" id="assigned-plate-row">
                            <div class="col-md-6" id="assigned-col">
                                <label class="form-label small fw-bold text-uppercase text-muted">Assigned To <span class="assigned-required text-danger" style="display: none;">*</span></label>
                                <input type="text" name="assigned_to" id="assigned_to" class="form-control shadow-sm assigned-input" value="{{ $ledger->assigned_to }}" placeholder="Personnel Name (Optional)">
                            </div>
                            <div class="col-md-6" id="plate-col">
                                <label class="form-label small fw-bold text-uppercase text-muted">Plate No. <span class="plate-required text-danger" style="display: none;">*</span></label>
                                <input type="text" name="plate_no" id="plate_no" class="form-control shadow-sm plate-input" value="{{ $ledger->plate_no }}" placeholder="Vehicle Plate No. (Optional)">
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-uppercase text-muted">Remarks <span class="remarks-required text-danger" style="display: none;">*</span></label>
                                <textarea name="remarks" id="remarks" class="form-control shadow-sm remarks-input" rows="3" placeholder="Optional">{{ $ledger->remarks }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="{{ route('ledgers.show', $ledger) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                        <i class="bi bi-check-circle"></i> Update Ledger Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const destSelect = document.querySelector('.dest-warehouse-select');
    if (destSelect) {
        new TomSelect(destSelect, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    }

    const itemSelect = document.getElementById('item_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    const typeSelect = document.getElementById('type');
    const actionSelect = document.getElementById('action');
    const unitDisplay = document.getElementById('unit-display');
    const allocationSelect = document.getElementById('allocation_id');

    const initialAction = @json($ledger->action);

    // Event listeners
    itemSelect.addEventListener('change', function() {
        updateUnitDisplay();
        updateActionOptions();
    });

    warehouseSelect.addEventListener('change', function() {
        updateAllocationOptions();
    });

    typeSelect.addEventListener('change', function() {
        updateActionOptions();
    });

    actionSelect.addEventListener('change', function() {
        toggleFields();
    });

    // Initialize
    updateUnitDisplay();
    updateActionOptions();

    function updateUnitDisplay() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (selectedOption) {
            unitDisplay.textContent = selectedOption.getAttribute('data-unit') || 'Units';
        }
    }

    function updateActionOptions() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (!selectedOption) return;

        const itemType = selectedOption.getAttribute('data-type');
        const movementType = typeSelect.value;

        const warehouseOption = warehouseSelect.options[warehouseSelect.selectedIndex];
        const isCentral = warehouseOption ? warehouseOption.getAttribute('data-is-central') === 'true' : false;

        // Clear existing action options
        actionSelect.innerHTML = '';

        let options = [];

        if (movementType === 'IN') {
            options = [
                { value: 'DELIVERY', text: 'DELIVERY (Inbound from Supplier)' },
                { value: 'INITIAL_STOCK', text: 'INITIAL STOCK (Warehouse Opening)' }
            ];

            if (itemType === 'ASSET') {
                options.push({ value: 'ASSET_RETURN', text: 'ASSET RETURN (From Site/Staff)' });
            }

            options.push({ value: 'TRANSFER', text: 'TRANSFER (Auto-logged Receipt)' });
        } else if (movementType === 'OUT') {
            options = [
                { value: 'TRANSFER', text: 'TRANSFER (Send to Warehouse)' },
                { value: 'REJECT', text: 'REJECT (Return To Vendor)' },
                { value: 'MAINTENANCE', text: 'MAINTENANCE (Repair/Checkup)' },
                { value: 'DISPOSE', text: 'DISPOSE (Scrap/Waste)' },
                { value: 'LOST', text: 'LOST (Missing/Damage)' },
                { value: 'UTILIZE', text: 'UTILIZE (Use/Consume)' }
            ];

            if (itemType === 'CONSUMABLE') {
                options = options.filter(opt => opt.value !== 'MAINTENANCE');
            }

            if (itemType === 'ASSET') {
                options = options.filter(opt => opt.value !== 'ALLOCATE');
            }

            if (isCentral) {
                options = options.filter(opt => opt.value !== 'ALLOCATE');
            } else if (itemType === 'CONSUMABLE') {
                options.push({ value: 'ALLOCATE', text: 'ALLOCATE (Direct to Allocation)' });
            }
        }

        // Add CORRECTION since we are Admins
        options.push({ value: 'CORRECTION', text: 'CORRECTION' });

        options.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.text;
            if (opt.value === initialAction) {
                el.selected = true;
            }
            actionSelect.appendChild(el);
        });

        // Trigger toggleFields
        toggleFields();
    }

    function toggleFields() {
        const type = typeSelect.value;
        const action = actionSelect.value;

        const inTransfer = document.getElementById('in-transfer-fields');
        const outTransfer = document.getElementById('out-transfer-fields');
        const outAllocate = document.getElementById('out-allocate-fields');
        const receiptRow = document.getElementById('receipt-row');
        const assignedPlateRow = document.getElementById('assigned-plate-row');
        const assignedCol = document.getElementById('assigned-col');
        const plateCol = document.getElementById('plate-col');

        const plateInput = document.getElementById('plate_no');
        const assignedInput = document.getElementById('assigned_to');
        const remarksInput = document.getElementById('remarks');

        const plateAsterisk = document.querySelector('.plate-required');
        const assignedAsterisk = document.querySelector('.assigned-required');
        const remarksAsterisk = document.querySelector('.remarks-required');

        const selectedItemOption = itemSelect.options[itemSelect.selectedIndex];
        const itemType = selectedItemOption ? selectedItemOption.getAttribute('data-type') : null;

        const warehouseOption = warehouseSelect.options[warehouseSelect.selectedIndex];
        const isCentral = warehouseOption ? warehouseOption.getAttribute('data-is-central') === 'true' : false;

        // Reset visibility
        inTransfer.style.display = 'none';
        outTransfer.style.display = 'none';
        outAllocate.style.display = 'none';
        receiptRow.style.display = 'none';
        plateAsterisk.style.display = 'none';
        plateInput.required = false;
        plateInput.placeholder = 'Vehicle Plate No. (Optional)';

        if (assignedPlateRow) assignedPlateRow.style.display = 'flex';
        if (assignedCol) assignedCol.style.display = 'block';
        if (plateCol) plateCol.style.display = 'block';

        if (assignedInput) {
            assignedInput.required = false;
            assignedInput.placeholder = 'Personnel Name (Optional)';
        }
        if (assignedAsterisk) assignedAsterisk.style.display = 'none';

        if (type === 'IN') {
            if (itemType === 'CONSUMABLE' && action === 'DELIVERY' && !isCentral) {
                outAllocate.style.display = 'block';
            }

            const isAssetReturn = action === 'ASSET_RETURN';
            const isInitialStock = action === 'INITIAL_STOCK';
            const showReceipts = action === 'DELIVERY' || action === 'TRANSFER';
            if (showReceipts && !isInitialStock) receiptRow.style.display = 'flex';

            if (isInitialStock) {
                if (assignedPlateRow) assignedPlateRow.style.display = 'none';
            }

            // Requirements for receipts and plate no
            const isMandatory = ['CONSUMABLE', 'ASSET'].includes(itemType) && action === 'DELIVERY';

            if (isMandatory) {
                plateInput.required = true;
                plateInput.placeholder = 'Vehicle Plate No. (Required)';
                plateAsterisk.style.display = 'inline';
            }

            document.querySelectorAll('.receipt-input').forEach(input => {
                if (input.id === 'po_number' || input.id === 'delivery_receipt') {
                    input.required = isMandatory;
                    input.placeholder = isMandatory ? 'Required' : 'Optional';
                }
            });
            document.querySelectorAll('.required-asterisk').forEach(ast => ast.style.display = isMandatory ? 'inline' : 'none');

            // Remarks Requirement
            remarksInput.required = isAssetReturn || isInitialStock;
            remarksAsterisk.style.display = (isAssetReturn || isInitialStock) ? 'inline' : 'none';
            if (isInitialStock) {
                remarksInput.placeholder = 'Required: Please state INITIAL STOCK details';
            } else {
                remarksInput.placeholder = 'Optional';
            }
        } else {
            if (action === 'TRANSFER') {
                outTransfer.style.display = 'block';
                plateInput.required = true;
                plateInput.placeholder = 'Vehicle Plate No. (Required)';
                plateAsterisk.style.display = 'inline';
            }
            if (action === 'ALLOCATE') {
                outAllocate.style.display = 'block';
            }
            if (action === 'REJECT') {
                receiptRow.style.display = 'flex';
            }
            if (action === 'UTILIZE') {
                if (itemType === 'ASSET') {
                    if (assignedPlateRow) assignedPlateRow.style.display = 'flex';
                    if (plateCol) plateCol.style.display = 'none';
                    if (assignedInput) {
                        assignedInput.required = true;
                        assignedInput.placeholder = 'Personnel Name (Required)';
                    }
                    if (assignedAsterisk) assignedAsterisk.style.display = 'inline';
                } else {
                    if (assignedPlateRow) assignedPlateRow.style.display = 'none';
                }
            }

            // Requirements for receipts (Shared logic for REJECT)
            const isReceiptMandatory = action === 'REJECT';
            document.querySelectorAll('.receipt-input').forEach(input => {
                if (input.id === 'po_number' || input.id === 'delivery_receipt') {
                    input.required = isReceiptMandatory;
                    input.placeholder = isReceiptMandatory ? 'Required' : 'Optional';
                }
            });
            document.querySelectorAll('.required-asterisk').forEach(ast => ast.style.display = isReceiptMandatory ? 'inline' : 'none');

            // Remarks Requirement for LOST, DISPOSE, MAINTENANCE, REJECT, UTILIZE
            const requiresRemarks = ['LOST', 'DISPOSE', 'MAINTENANCE', 'REJECT', 'UTILIZE'].includes(action);
            remarksInput.required = requiresRemarks;
            remarksAsterisk.style.display = requiresRemarks ? 'inline' : 'none';

            if (requiresRemarks) {
                remarksInput.placeholder = `Required: Please state reason for ${action.toLowerCase()}`;
            } else {
                remarksInput.placeholder = 'Optional';
            }
        }
    }

    async function updateAllocationOptions() {
        const warehouseId = warehouseSelect.value;
        if (!warehouseId) return;

        try {
            const response = await fetch(`/ledgers/allocations-by-warehouse?warehouse_id=${warehouseId}`);
            if (response.ok) {
                const allocations = await response.json();
                allocationSelect.innerHTML = '<option value="">Select Target Allocation...</option>';
                allocations.forEach(allocation => {
                    const el = document.createElement('option');
                    el.value = allocation.id;
                    el.textContent = allocation.name;
                    if (allocation.id == @json($ledger->allocation_id)) {
                        el.selected = true;
                    }
                    allocationSelect.appendChild(el);
                });
            }
        } catch (error) {
            console.error('Error fetching allocations:', error);
        }
    }
});
</script>
@endsection
