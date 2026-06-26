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
                        <th class="py-3 text-center">Consumable</th>
                        <th class="py-3 text-center">Asset</th>
                        <th class="py-3 text-center">Recoverable</th>
                        <th class="py-3">Usage</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DELIVERY -->
                    <tr>
                        <td><strong>DELIVERY</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Use this for purchased items that are going in the warehouse.</td>
                    </tr>
                    <!-- INITIAL_STOCK -->
                    <tr>
                        <td><strong>INITIAL_STOCK</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Use this when setting inital item stock of the warehouse.</td>
                    </tr>
                    <!-- DIRECT -->
                    <tr>
                        <td><strong>DIRECT</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td class="text-center text-muted">-</td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Use this for Assets and Recoverables that are delivered to the warehouse.</td>
                    </tr>
                    <!-- TRANSFER -->
                    <tr>
                        <td><strong>TRANSFER</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Use this if the items are transfered from one warehouse to another.</td>
                    </tr>
                    <!-- ALLOCATE -->
                    <tr>
                        <td><strong>ALLOCATE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center text-muted">-</td>
                        <td class="text-center text-muted">-</td>
                        <td>Use this if the the item being logged out is used for a budgeted work item (Component).</td>
                    </tr>
                    <!-- UTILIZE -->
                    <tr>
                        <td><strong>UTILIZE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Use this if the the item being logged out is utlized, but not used for any budgeted work item (Component).</td>
                    </tr>
                    <!-- DISPOSE -->
                    <tr>
                        <td><strong>DISPOSE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Used for materials logged out as scrap or waste.</td>
                    </tr>
                    <!-- LOST -->
                    <tr>
                        <td><strong>LOST</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Deducts stock levels for missing or stolen items.</td>
                    </tr>
                    <!-- RETURN -->
                    <tr>
                        <td><strong>RETURN</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Used for sending items back to supplier/Central.</td>
                    </tr>
                    <!-- MAINTENANCE -->
                    <tr>
                        <td><strong>MAINTENANCE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td class="text-center text-muted">-</td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                        <td>Logs out items temporarily for service.</span></td>
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
                <li class="mb-1"><strong>Asset/Recoverable Quantity Constraint:</strong> Asset and Recoverable items can only be processed **one at a time** (quantity must always be **exactly 1.00** per entry).</li>
                <li class="mb-1"><strong>Stock Level Check:</strong> You cannot log out (`OUT`) more quantity than what is currently available in the active warehouse.</li>
                <li><strong>Central vs. Site Rules:</strong> Central warehouses deal with deliveries and bulk transfers; Site warehouses can allocate consumables down to specific project tasks.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
