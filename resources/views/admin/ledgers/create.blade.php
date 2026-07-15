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
                <div>
                    <h3 class="fw-bold text-primary mb-0"><i class="bi bi-journal-plus"></i> {{ __('Unified Ledger Entry') }}</h3>
                    @if($warehouse && $warehouse->project)
                        <div class="text-muted mt-1"><i class="bi bi-building me-1"></i> Assigned Project: <strong>{{ $warehouse->project->name }}</strong></div>
                    @endif
                </div>
                @if(request('warehouse_id'))
                    <a href="{{ route('logger.warehouse.dashboard', request('warehouse_id')) }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                @endif
            </div>

            {{-- Section 1: Item Selection --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-search"></i> 1. Select Item</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-muted text-uppercase small">Search and Select Item</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white border-primary border-opacity-25 text-primary">
                                    <i class="bi bi-box-seam"></i>
                                </span>
                                <input type="text" id="global-item-search" class="form-control border-primary border-opacity-25" placeholder="Type item name to search..." list="item-options" autocomplete="off">
                            </div>
                            <div id="selected-item-info" class="mt-3" style="display: none;">
                                <span class="badge bg-info text-dark px-3 py-2 rounded-pill shadow-sm">
                                    <i class="bi bi-info-circle me-1"></i> Selected: <strong id="display-item-name"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center d-none d-md-block">
                            <div class="text-muted small">
                                <i class="bi bi-lightbulb text-warning"></i> 
                                Search for an item first to enable adding entries.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Entries --}}
            <form method="POST" action="{{ route('ledgers.store') }}" id="ledger-form">
                @csrf
                <input type="hidden" name="global_item_id" id="global-item-id">
                
                <div id="entries-section" style="display: none;">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-list-check"></i> 2. Entry Details</h5>
                            <button type="button" class="btn btn-sm btn-outline-light" id="add-entry-btn">
                                <i class="bi bi-plus-circle"></i> Add Movement
                            </button>
                        </div>
                        <div class="card-body bg-light bg-opacity-50 p-4">
                            <div id="entries-container">
                                <!-- Rows will be injected here -->
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-primary px-4 shadow-sm" id="add-entry-btn-footer">
                                    <i class="bi bi-plus-circle"></i> Add Another Movement
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                            <i class="bi bi-check2-all"></i> Submit Ledger Entries
                        </button>
                    </div>
                </div>
            </form>
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
                <div class="me-5">
                    <div class="small text-muted text-uppercase fw-bold">Current Stock</div>
                    <div class="h4 mb-0 fw-bold">
                        <span id="display-item-stock">0</span>
                    </div>
                </div>
                <div>
                    <div class="small text-warning text-uppercase fw-bold">Calculated Balance</div>
                    <div class="h4 mb-0 fw-bold text-warning">
                        <span id="calculated-item-stock">0</span>
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

{{-- Global Items Datalist for Search --}}
<datalist id="item-options">
    @foreach($items as $item)
        <option value="{{ $item->name }}{{ $item->specification ? ' ' . $item->specification : '' }} {{ $item->unit }} ({{ $item->type }})" data-id="{{ $item->id }}" data-type="{{ $item->type }}"></option>
    @endforeach
</datalist>

{{-- Template for a single entry row --}}
<template id="entry-template">
    <div class="card mb-4 entry-row border-0 shadow-sm border-start border-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h6 class="card-title mb-0 fw-bold">Movement #<span class="entry-index-display"></span></h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
            
            <input type="hidden" name="entries[__INDEX__][warehouse_id]" value="{{ $selectedWarehouseId }}">
            <input type="hidden" name="entries[__INDEX__][item_id]" class="row-item-id">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Entry Date</label>
                    <input type="date" name="entries[__INDEX__][entry_date]" class="form-control shadow-sm" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Movement Type</label>
                    <select name="entries[__INDEX__][type]" class="form-select type-select shadow-sm" required>
                        <option value="IN">Log IN (Entry)</option>
                        <option value="OUT">Log OUT (Withdrawal)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Action</label>
                    <select name="entries[__INDEX__][action]" class="form-select action-select shadow-sm" required>
                        <option value="">Select Type First...</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Quantity</label>
                    <div class="input-group shadow-sm">
                        <input type="number" step="0.01" name="entries[__INDEX__][quantity]" class="form-control" required min="0.01">
                        <span class="input-group-text text-muted small px-2 row-unit-display">Units</span>
                    </div>
                </div>
            </div>

            {{-- Conditional Fields --}}
            <div class="row g-3 mt-1">
                {{-- IN TRANSFER: Source --}}
                <div class="col-md-6 in-transfer-fields" style="display: none;">
                    <label class="form-label small fw-bold text-uppercase text-muted">Transfer Source (FROM)</label>
                    <select name="entries[__INDEX__][source_warehouse_id]" class="form-select shadow-sm border-success border-opacity-25">
                        <option value="">Select Source Warehouse...</option>
                        @foreach($warehouses as $wh)
                            @if($wh->id != $selectedWarehouseId)
                                <option value="{{ $wh->id }}">
                                    @if($wh->parent)
                                        {{ $wh->parent->name }} &gt; {{ $wh->name }} (Sub WH)
                                    @else
                                        {{ $wh->name }}
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="entries[__INDEX__][destination_warehouse_id]" class="destination-warehouse-hidden">
                </div>

                {{-- OUT TRANSFER: Destination --}}
                <div class="col-md-6 out-transfer-fields" style="display: none;">
                    <label class="form-label small fw-bold text-uppercase text-muted">Transfer Destination (TO)</label>
                    <select name="entries[__INDEX__][destination_warehouse_id]" class="form-select shadow-sm border-danger border-opacity-25 dest-warehouse-select">
                        <option value="">Select Destination Warehouse...</option>
                        @foreach($warehouses as $wh)
                            @if($wh->id != $selectedWarehouseId)
                                <option value="{{ $wh->id }}">
                                    @if($wh->parent)
                                        {{ $wh->parent->name }} &gt; {{ $wh->name }} (Sub WH)
                                    @else
                                        {{ $wh->name }}
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="entries[__INDEX__][source_warehouse_id]" class="source-warehouse-hidden">
                </div>

                {{-- OUT ALLOCATE: Allocation --}}
                <div class="col-md-6 out-allocate-fields" style="display: none;">
                    <label class="form-label small fw-bold text-uppercase text-muted">Allocation Target</label>
                    <select name="entries[__INDEX__][allocation_id]" class="form-select allocation-select shadow-sm border-danger border-opacity-25">
                        <option value="">Select Target Allocation...</option>
                        @foreach($allocations as $allocation)
                            @php
                                $showAlloc = false;
                                // Only show allocations if they belong to the current warehouse
                                if($warehouse && $allocation->warehouse_id == $warehouse->id) {
                                    $showAlloc = true;
                                }
                            @endphp
                            @if($showAlloc)
                                <option value="{{ $allocation->id }}">{{ $allocation->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Receipt Fields (IN DELIVERY) --}}
            <div class="row g-3 mt-1 receipt-row" style="display: none;">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">PO Number <span class="required-asterisk text-danger" style="display: none;">*</span></label>
                    <input type="text" name="entries[__INDEX__][po_number]" class="form-control shadow-sm receipt-input" data-mandatory="true" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Delivery Receipt <span class="required-asterisk text-danger" style="display: none;">*</span></label>
                    <input type="text" name="entries[__INDEX__][delivery_receipt]" class="form-control shadow-sm receipt-input" data-mandatory="true" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Official Receipt</label>
                    <input type="text" name="entries[__INDEX__][offical_receipt]" class="form-control shadow-sm receipt-input" placeholder="Optional">
                </div>
            </div>

            <div class="row g-3 mt-1 assigned-plate-row">
                <div class="col-md-6 assigned-col">
                    <label class="form-label small fw-bold text-uppercase text-muted">Assigned To <span class="assigned-required text-danger" style="display: none;">*</span></label>
                    <input type="text" name="entries[__INDEX__][assigned_to]" class="form-control shadow-sm assigned-input" placeholder="Personnel Name (Optional)">
                </div>
                <div class="col-md-6 plate-col">
                    <label class="form-label small fw-bold text-uppercase text-muted">Plate No. <span class="plate-required text-danger" style="display: none;">*</span></label>
                    <input type="text" name="entries[__INDEX__][plate_no]" class="form-control shadow-sm plate-input" placeholder="Vehicle Plate No. (Optional)">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label class="form-label small fw-bold text-uppercase text-muted">Remarks <span class="remarks-required text-danger" style="display: none;">*</span></label>
                    <textarea name="entries[__INDEX__][remarks]" class="form-control shadow-sm remarks-input" rows="2" placeholder="Optional"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .entry-row { transition: all 0.2s ease; }
    .entry-row.row-in { border-left-color: #198754 !important; }
    .entry-row.row-out { border-left-color: #dc3545 !important; }
    .required-asterisk, .remarks-required { font-size: 1.2em; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSearch = document.getElementById('global-item-search');
    const itemsDatalist = document.getElementById('item-options');
    const selectedItemInfo = document.getElementById('selected-item-info');
    const displayName = document.getElementById('display-item-name');
    const displayStock = document.getElementById('display-item-stock');
    const calculatedStockDisplay = document.getElementById('calculated-item-stock');
    const footerItemName = document.getElementById('footer-item-name');
    const stockFooter = document.getElementById('fixed-stock-footer');
    const globalItemIdInput = document.getElementById('global-item-id');
    const entriesSection = document.getElementById('entries-section');
    const container = document.getElementById('entries-container');
    const template = document.getElementById('entry-template').innerHTML;
    
    const isAdmin = @json(Auth::check() && Auth::user()->isAdmin());
    const currentWarehouseId = @json($selectedWarehouseId);
    const selectedItemId = @json($selectedItemId);
    const isCentral = @json($warehouse && $warehouse->type !== 'SITE');
    
    let entryIndex = 0;
    let currentItem = { id: null, type: null, name: null };
    let currentAllocations = isCentral ? [] : @json($allocations->where('warehouse_id', $warehouse ? $warehouse->id : 0)->values());
    let baseStock = 0;

    function updateCalculatedStock() {
        let currentChange = 0;
        document.querySelectorAll('.entry-row').forEach(row => {
            const type = row.querySelector('.type-select').value;
            const qtyInput = row.querySelector('input[name*="[quantity]"]');
            const quantity = parseFloat(qtyInput.value) || 0;
            
            if (type === 'IN') {
                currentChange += quantity;
            } else if (type === 'OUT') {
                currentChange -= quantity;
            }
        });

        const newTotal = baseStock + currentChange;
        calculatedStockDisplay.textContent = newTotal.toFixed(2).replace(/\.00$/, '');
        
        if (newTotal < 0) {
            calculatedStockDisplay.classList.replace('text-warning', 'text-danger');
        } else {
            calculatedStockDisplay.classList.replace('text-danger', 'text-warning');
        }
    }

    // 1. Item Selection Logic
    itemSearch.addEventListener('input', async function() {
        const val = this.value;
        const options = itemsDatalist.options;
        let found = false;

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === val) {
                currentItem.id = options[i].getAttribute('data-id');
                currentItem.type = options[i].getAttribute('data-type');
                currentItem.name = val;
                found = true;
                break;
            }
        }

        if (found) {
            globalItemIdInput.value = currentItem.id;
            displayName.textContent = currentItem.name;
            footerItemName.textContent = currentItem.name;
            selectedItemInfo.style.display = 'block';
            entriesSection.style.display = 'block';
            stockFooter.style.display = 'block';
            document.body.style.paddingBottom = '80px';

            // ASSET RULE: One at a time
            const addBtns = document.querySelectorAll('#add-entry-btn, #add-entry-btn-footer');
            if (currentItem.type === 'ASSET') {
                addBtns.forEach(btn => btn.style.display = 'none');
                // Remove extra rows if switching from others to Asset
                while (container.children.length > 1) {
                    container.lastChild.remove();
                }
            } else {
                addBtns.forEach(btn => btn.style.display = 'inline-block');
            }

            // Sync first row if it exists
            const firstQtyInput = container.querySelector('input[name*="[quantity]"]');
            if (firstQtyInput) {
                if (currentItem.type === 'ASSET') {
                    firstQtyInput.value = 1;
                    firstQtyInput.readOnly = true;
                } else {
                    firstQtyInput.readOnly = false;
                    // Reset quantity if it was forced to 1 for Asset and now it's not
                    if (firstQtyInput.value == 1 && firstQtyInput.hasAttribute('readonly')) {
                         // firstQtyInput.value = ''; // Optional: clear it
                    }
                }
            }

            // Fetch Stock Information
            displayStock.textContent = '...';
            calculatedStockDisplay.textContent = '...';
            
            try {
                const stockUrl = `{{ url('items') }}/${currentItem.id}/stock?warehouse_id=${currentWarehouseId || ''}`;
                const response = await fetch(stockUrl);
                const data = await response.json();
                
                baseStock = parseFloat(data.balance) || 0;
                displayStock.textContent = data.balance + ' ' + data.unit;
                updateCalculatedStock();

                // Update all row units
                document.querySelectorAll('.row-unit-display').forEach(el => {
                    el.textContent = data.unit;
                });
            } catch (error) {
                console.error('Error fetching stock:', error);
                displayStock.textContent = 'Error';
            }
            
            // Sync all existing rows with new item ID
            document.querySelectorAll('.row-item-id').forEach(input => input.value = currentItem.id);
            // Refresh action options for all rows
            document.querySelectorAll('.entry-row').forEach(row => updateActionOptions(row));
            
            // If first time or empty, add a row
            if (container.children.length === 0) {
                addEntry();
            }
        } else {
            // Optional: reset if cleared
            if (val === '') {
                currentItem = { id: null, type: null, name: null };
                selectedItemInfo.style.display = 'none';
                entriesSection.style.display = 'none';
                stockFooter.style.display = 'none';
                document.body.style.paddingBottom = '0';
                baseStock = 0;
            }
        }
    });

    // Pre-fill Item if selectedItemId is present (Moved here to ensure listener is active)
    if (selectedItemId) {
        const options = itemsDatalist.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].getAttribute('data-id') == selectedItemId) {
                itemSearch.value = options[i].value;
                itemSearch.dispatchEvent(new Event('input'));
                break;
            }
        }
    }

    // 2. Dynamic Row Logic
    function addEntry() {
        if (!currentItem.id) return;
        if (currentItem.type === 'ASSET' && container.children.length >= 1) return;

        const index = entryIndex++;
        const html = template.replace(/__INDEX__/g, index);
        
        const div = document.createElement('div');
        div.innerHTML = html.trim();
        const element = div.firstChild;
        
        element.querySelector('.row-item-id').value = currentItem.id;
        
        const typeSelect = element.querySelector('.type-select');
        const actionSelect = element.querySelector('.action-select');
        const allocSelect = element.querySelector('.allocation-select');
        const qtyInput = element.querySelector('input[name*="[quantity]"]');
        const removeBtn = element.querySelector('.remove-entry-btn');

        if (currentItem.type === 'ASSET') {
            qtyInput.value = 1;
            qtyInput.readOnly = true;
        }

        // Initial population of allocations (for SITE warehouses or cached selections)
        if (currentAllocations.length > 0) {
            allocSelect.innerHTML = '<option value="">Select Target Allocation...</option>';
            currentAllocations.forEach(alloc => {
                allocSelect.add(new Option(alloc.name, alloc.id));
            });
        }
        
        typeSelect.addEventListener('change', () => {
            updateActionOptions(element);
            updateRowStyle(element);
            updateCalculatedStock();
        });
        actionSelect.addEventListener('change', () => toggleFields(element));
        
        qtyInput.addEventListener('input', updateCalculatedStock);

        removeBtn.addEventListener('click', function() {
            if (container.querySelectorAll('.entry-row').length > 1) {
                element.remove();
                updateIndices();
                updateCalculatedStock();
            } else {
                alert('At least one entry is required.');
            }
        });
        
        container.appendChild(element);

        // Initialize Tom Select for Destination select tag to be searchable
        const destSelect = element.querySelector('.dest-warehouse-select');
        if (destSelect) {
            new TomSelect(destSelect, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }

        updateActionOptions(element);
        updateRowStyle(element);
        updateIndices();
        updateCalculatedStock();
    }

    function updateActionOptions(row) {
        const typeSelect = row.querySelector('.type-select');
        const actionSelect = row.querySelector('.action-select');
        const type = typeSelect.value;
        const currentAction = actionSelect.value;
        
        actionSelect.innerHTML = '<option value="">Select Action...</option>';
        
        let options = [];
        if (type === 'IN') {
            options = [
                { value: 'DELIVERY', text: 'DELIVERY (Purchases)' },
                { value: 'INITIAL_STOCK', text: 'INITIAL STOCK' }
            ];
            if (currentItem.type === 'ASSET') {
                options.splice(1, 0, { value: 'ASSET_RETURN', text: 'ASSET_RETURN (Asset Log-in)' });
            }
        } else {
            options = [
                { value: 'ALLOCATE', text: 'ALLOCATE (Target)' },
                { value: 'TRANSFER', text: 'TRANSFER (Send to Warehouse)' },
                { value: 'REJECT', text: 'REJECT (Return To Vendor)' },
                { value: 'MAINTENANCE', text: 'MAINTENANCE (Repair/Checkup)' },
                { value: 'DISPOSE', text: 'DISPOSE (Scrap/Waste)' },
                { value: 'LOST', text: 'LOST (Missing/Damage)' },
                { value: 'UTILIZE', text: 'UTILIZE (Use/Consume)' }
            ];

            if (currentItem.type === 'CONSUMABLE') {
                options = options.filter(opt => opt.value !== 'MAINTENANCE');
            }

            if (currentItem.type === 'ASSET') {
                options = options.filter(opt => opt.value !== 'ALLOCATE');
            }

            if (isCentral) {
                options = options.filter(opt => opt.value !== 'ALLOCATE');
            }
        }

        if (isAdmin) {
            options.push({ value: 'CORRECTION', text: 'CORRECTION' });
        }

        options.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.text;
            if (opt.value === currentAction) el.selected = true;
            actionSelect.appendChild(el);
        });

        toggleFields(row);
    }

    function toggleFields(row) {
        const type = row.querySelector('.type-select').value;
        const action = row.querySelector('.action-select').value;
        
        // Fields groups
        const inTransfer = row.querySelector('.in-transfer-fields');
        const outTransfer = row.querySelector('.out-transfer-fields');
        const outAllocate = row.querySelector('.out-allocate-fields');
        const receiptRow = row.querySelector('.receipt-row');
        const plateAsterisk = row.querySelector('.plate-required');
        const plateInput = row.querySelector('.plate-input');
        const remarksInput = row.querySelector('.remarks-input');
        const remarksAsterisk = row.querySelector('.remarks-required');
        const assignedPlateRow = row.querySelector('.assigned-plate-row');
        const assignedCol = row.querySelector('.assigned-col');
        const plateCol = row.querySelector('.plate-col');
        const assignedInput = row.querySelector('.assigned-input');
        const assignedAsterisk = row.querySelector('.assigned-required');
        
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
            // TRANSFER (IN) is now hidden from manual selection as it is handled automatically from OUT-TRANSFER
            
            // Show allocation for CONSUMABLE items on DELIVERY IN (Except for CENTRAL warehouses)
            if (currentItem.type === 'CONSUMABLE' && action === 'DELIVERY' && !isCentral) {
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
            const isMandatory = ['CONSUMABLE', 'ASSET'].includes(currentItem.type) && action === 'DELIVERY';
            
            if (isMandatory) {
                plateInput.required = true;
                plateInput.placeholder = 'Vehicle Plate No. (Required)';
                plateAsterisk.style.display = 'inline';
            }

            row.querySelectorAll('.receipt-input').forEach(input => {
                if (input.getAttribute('data-mandatory') === 'true') {
                    input.required = isMandatory;
                    input.placeholder = isMandatory ? 'Required' : 'Optional';
                }
            });
            row.querySelectorAll('.required-asterisk').forEach(ast => ast.style.display = isMandatory ? 'inline' : 'none');
            
            // Remarks Requirement
            remarksInput.required = isAssetReturn || isInitialStock;
            remarksAsterisk.style.display = (isAssetReturn || isInitialStock) ? 'inline' : 'none';
            if (isInitialStock) {
                remarksInput.placeholder = 'Required: Please state INITIAL STOCK details';
            } else {
                remarksInput.placeholder = 'Optional';
            }
        } else {
            const isDispose = action === 'DISPOSE';
            
            if (action === 'TRANSFER') {
                outTransfer.style.display = 'block';
                row.querySelector('.source-warehouse-hidden').value = currentWarehouseId || '';
                
                // Plate No mandatory for Transfer
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
                if (currentItem.type === 'ASSET') {
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
            row.querySelectorAll('.receipt-input').forEach(input => {
                if (input.getAttribute('data-mandatory') === 'true') {
                    input.required = isReceiptMandatory;
                    input.placeholder = isReceiptMandatory ? 'Required' : 'Optional';
                }
            });
            row.querySelectorAll('.required-asterisk').forEach(ast => ast.style.display = isReceiptMandatory ? 'inline' : 'none');

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

    function updateRowStyle(row) {
        const type = row.querySelector('.type-select').value;
        row.classList.toggle('row-in', type === 'IN');
        row.classList.toggle('row-out', type === 'OUT');
    }

    function updateIndices() {
        document.querySelectorAll('.entry-row').forEach((row, idx) => {
            row.querySelector('.entry-index-display').textContent = idx + 1;
        });
    }

    document.getElementById('add-entry-btn').addEventListener('click', addEntry);
    document.getElementById('add-entry-btn-footer').addEventListener('click', addEntry);

    // 3. AJAX Submission
    const ledgerForm = document.getElementById('ledger-form');
    const errorModalElement = document.getElementById('errorModal');
    const errorModalMessage = document.getElementById('errorModalMessage');

    ledgerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        try {
            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.location.href = result.redirect;
            } else {
                throw new Error(result.message || 'An unexpected error occurred.');
            }
        } catch (error) {
            console.error('Submission error:', error);
            errorModalMessage.textContent = error.message;
            
            // Robust Modal Trigger
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(errorModalElement);
                modal.show();
            } else if (window.bootstrap && window.bootstrap.Modal) {
                const modal = new window.bootstrap.Modal(errorModalElement);
                modal.show();
            } else {
                // Fallback to manual trigger using data attributes if JS object is missing
                errorModalElement.classList.add('show');
                errorModalElement.style.display = 'block';
                document.body.classList.add('modal-open');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                
                // Use event delegation for all dismiss buttons
                errorModalElement.addEventListener('click', (event) => {
                    if (event.target.closest('[data-bs-dismiss="modal"]')) {
                        errorModalElement.classList.remove('show');
                        errorModalElement.style.display = 'none';
                        document.body.classList.remove('modal-open');
                        backdrop.remove();
                    }
                });
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    });
});
</script>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="errorModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Entry Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                </div>
                <p id="errorModalMessage" class="fs-5 mb-0 text-muted"></p>
            </div>
            <div class="modal-footer border-0 pb-4 justify-content-center">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Close and Correct</button>
            </div>
        </div>
    </div>
</div>
@endsection
