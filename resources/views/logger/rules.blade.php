@extends('layouts.logger')

@section('inner_content')
<div class="container-fluid p-0">
    <!-- Header Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4 bg-primary text-white rounded">
            <div class="d-flex align-items-center">
                <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                    <i class="bi bi-info-circle-fill fs-3"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold">Movement Rules Guide</h4>
                    <p class="mb-0 opacity-75">Your complete reference guide to item logging combinations, validation requirements, and action types.</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Movement Rules Combinations Matrix -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i> Movement Combinations & Rules Matrix</h5>
    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="font-size: 0.85rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="py-3">Action</th>
                        <th class="py-3">Type</th>
                        <th class="py-3 text-center">Bulk</th>
                        <th class="py-3 text-center">Asset</th>
                        <th class="py-3">Usage & Validation Requirements</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DELIVERY -->
                    <tr>
                        <td><strong>DELIVERY</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Purchased items received into warehouse stock.</div>
                            <small class="text-muted">Requires: PO Number, Delivery Receipt, Vehicle Plate No.</small>
                        </td>
                    </tr>
                    <!-- INITIAL_STOCK -->
                    <tr>
                        <td><strong>INITIAL_STOCK</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Setting opening/initial stock levels for a warehouse.</div>
                            <small class="text-muted">Requires: Remarks explaining stock origin.</small>
                        </td>
                    </tr>
                    <!-- RETURN_ASSET -->
                    <tr>
                        <td><strong>RETURN_ASSET</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center text-muted">-</td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Receiving assigned/utilized equipment back into warehouse stock.</div>
                            <small class="text-muted">Requires: Remarks. Item must have an active OUT-UTILIZE record in the warehouse.</small>
                        </td>
                    </tr>
                    <!-- RETURN_ALLOCATION -->
                    <tr>
                        <td><strong>RETURN_ALLOCATION</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center text-muted">-</td>
                        <td>
                            <div>Returning unconsumed bulk materials from a project allocation back into warehouse stock.</div>
                            <small class="text-muted">Requires: Target Allocation, Project. Quantity cannot exceed net allocated amount; automatically deducts from reports.</small>
                        </td>
                    </tr>
                    <!-- TRANSFER (IN) -->
                    <tr>
                        <td><strong>TRANSFER</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Inbound arrival of items transferred from another warehouse.</div>
                            <small class="text-muted">Auto-generated automatically whenever an OUT-TRANSFER is dispatched.</small>
                        </td>
                    </tr>
                    <!-- TRANSFER (OUT) -->
                    <tr>
                        <td><strong>TRANSFER</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Transferring materials or equipment to another warehouse.</div>
                            <small class="text-muted">Requires: Destination Warehouse, Vehicle Plate No.</small>
                        </td>
                    </tr>
                    <!-- ALLOCATE -->
                    <tr>
                        <td><strong>ALLOCATE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center text-muted">-</td>
                        <td>
                            <div>Issuing bulk materials towards a budgeted project work item/component.</div>
                            <small class="text-muted">Requires: Target Allocation. Tracked on Project Allocation Reports.</small>
                        </td>
                    </tr>
                    <!-- UTILIZE -->
                    <tr>
                        <td><strong>UTILIZE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Issuing items for operational consumption or deploying assets to personnel.</div>
                            <small class="text-muted">Requires: Remarks. If Asset, strictly requires Assigned Personnel.</small>
                        </td>
                    </tr>
                    <!-- MAINTENANCE -->
                    <tr>
                        <td><strong>MAINTENANCE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center text-muted">-</td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Logging out equipment temporarily for servicing, repair, or calibration.</div>
                            <small class="text-muted">Requires: Remarks specifying maintenance details.</small>
                        </td>
                    </tr>
                    <!-- DISPOSE -->
                    <tr>
                        <td><strong>DISPOSE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Removing obsolete, expired, or condemned materials as scrap/waste.</div>
                            <small class="text-muted">Requires: Remarks.</small>
                        </td>
                    </tr>
                    <!-- LOST -->
                    <tr>
                        <td><strong>LOST</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Deducting inventory for missing, damaged, or stolen items.</div>
                            <small class="text-muted">Requires: Remarks detailing loss incident.</small>
                        </td>
                    </tr>
                    <!-- REJECT -->
                    <tr>
                        <td><strong>REJECT</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Returning defective, substandard, or rejected goods back to the supplier.</div>
                            <small class="text-muted">Requires: Remarks, PO Number, Delivery Receipt.</small>
                        </td>
                    </tr>
                    <!-- CORRECTION -->
                    <tr>
                        <td><strong>CORRECTION</strong></td>
                        <td>
                            <span class="badge bg-success">IN</span>
                            <span class="badge bg-danger ms-1">OUT</span>
                        </td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>
                            <div>Manual inventory adjustment to align recorded stock with physical count.</div>
                            <small class="text-muted">Restricted to Administrators only. Requires: Remarks.</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- General Constraints Alert Card -->
    <div class="card border-0 shadow-sm border-start border-warning border-4 mb-4">
        <div class="card-body p-3">
            <h6 class="fw-bold text-warning-emphasis mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Crucial System Rules to Remember:</h6>
            <ul class="mb-0 text-muted small ps-3">
                <li class="mb-1"><strong>Asset Quantity Constraint:</strong> Asset items can only be processed **one at a time** (quantity must always be **exactly 1.00** per entry).</li>
                <li class="mb-1"><strong>Stock Level Check:</strong> You cannot log out (`OUT`) more quantity than what is currently available in the active warehouse.</li>
                <li class="mb-1"><strong>Return Validations:</strong>
                    <ul>
                        <li><code>RETURN_ASSET</code>: Can only return an asset that currently has an active <code>OUT &gt; UTILIZE</code> entry in the warehouse.</li>
                        <li><code>RETURN_ALLOCATION</code>: Can only return bulk items up to the net quantity previously logged under <code>OUT &gt; ALLOCATE</code> for that component.</li>
                    </ul>
                </li>
                <li><strong>Central vs. Site Rules:</strong> Central warehouses deal with deliveries and bulk transfers; Site warehouses can allocate bulk goods down to specific project tasks.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
