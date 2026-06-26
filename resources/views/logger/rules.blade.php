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

    <!-- Actions Explanations Section -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text me-2"></i> Explanation of Actions</h5>
    <div class="row g-3 mb-4">
        <!-- INITIAL_STOCK -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Initial Stock</h6>
                        <span class="badge bg-success small">IN</span>
                    </div>
                    <p class="text-muted small mb-0">Sets the starting inventory balance of items in a warehouse. Requires detailed Remarks to justify and document the initial count.</p>
                </div>
            </div>
        </div>

        <!-- DELIVERY -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Delivery (Purchases)</h6>
                        <span class="badge bg-success small">IN</span>
                    </div>
                    <p class="text-muted small mb-0">Used for receiving newly purchased items from suppliers. Requires PO Number, Delivery Receipt (DR), and Vehicle Plate No.</p>
                </div>
            </div>
        </div>

        <!-- DIRECT -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Direct Asset Log-In</h6>
                        <span class="badge bg-success small">IN</span>
                    </div>
                    <p class="text-muted small mb-0">Directly registers an <strong>Asset</strong> or <strong>Recoverable</strong> item (e.g., tools, equipment) into system stock. Remarks are required.</p>
                </div>
            </div>
        </div>

        <!-- TRANSFER -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Transfer</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Moves items between warehouses. Automatically creates a pending <em>TRANSFER (IN)</em> entry at the destination. Requires Destination Warehouse and Vehicle Plate No.</p>
                </div>
            </div>
        </div>

        <!-- ALLOCATE -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Allocate</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Withdraws and allocates <strong>Consumable</strong> items to a specific target project activity. Only available in Site Warehouses. Requires an Allocation ID.</p>
                </div>
            </div>
        </div>

        <!-- UTILIZE -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Utilize</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Logs out items consumed directly in ongoing site operations. Hides optional fields (Assigned To, Plate No) to focus strictly on a required Remarks field stating the usage.</p>
                </div>
            </div>
        </div>

        <!-- DISPOSE -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Dispose (Scrap)</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Permanently removes scrap, expired, or unsalvageable waste materials. Requires detailed Remarks stating the reason for disposal.</p>
                </div>
            </div>
        </div>

        <!-- LOST -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Lost (Missing/Damage)</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Deducts inventory to record unaccounted, stolen, or accidentally damaged items. Detailed Remarks describing the incident are required.</p>
                </div>
            </div>
        </div>

        <!-- RETURN -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Return</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Sends items back to the supplier or Central Warehouse. Requires PO Number, Delivery Receipt (DR) Number, and explanatory Remarks.</p>
                </div>
            </div>
        </div>

        <!-- MAINTENANCE -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Maintenance</h6>
                        <span class="badge bg-danger small">OUT</span>
                    </div>
                    <p class="text-muted small mb-0">Logs out <strong>Asset</strong> or <strong>Recoverable</strong> equipment for external repair or service. Requires detailed Remarks detailing the fault.</p>
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
                        <th class="py-3">Allowed Item Types</th>
                        <th class="py-3">Required Input Fields</th>
                        <th class="py-3">Special Constraints & Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DELIVERY -->
                    <tr>
                        <td><strong>DELIVERY</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>PO No., Delivery Receipt, Plate No.</td>
                        <td>Primary action for purchasing new stock.</td>
                    </tr>
                    <!-- INITIAL_STOCK -->
                    <tr>
                        <td><strong>INITIAL_STOCK</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>Remarks</td>
                        <td>Sets stock on hand. Hides Assigned/Plate fields.</td>
                    </tr>
                    <!-- DIRECT -->
                    <tr>
                        <td><strong>DIRECT</strong></td>
                        <td><span class="badge bg-success">IN</span></td>
                        <td><span class="text-primary fw-bold">Asset, Recoverable</span></td>
                        <td>Remarks</td>
                        <td>Bypasses PO/DR. <span class="text-danger fw-bold">No Consumables allowed.</span></td>
                    </tr>
                    <!-- TRANSFER -->
                    <tr>
                        <td><strong>TRANSFER</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>Destination Warehouse, Plate No.</td>
                        <td>Automatically populates source warehouse ID and creates a pending TRANSFER (IN) at the target.</td>
                    </tr>
                    <!-- ALLOCATE -->
                    <tr>
                        <td><strong>ALLOCATE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td><span class="text-primary fw-bold">Consumable only</span></td>
                        <td>Allocation (Target Project Activity)</td>
                        <td><span class="text-danger fw-bold">Not available in Central Warehouses</span>. Hides plate/receipt fields.</td>
                    </tr>
                    <!-- UTILIZE -->
                    <tr>
                        <td><strong>UTILIZE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>Remarks</td>
                        <td>Hides all optional details (Assigned To, Plate No) to focus solely on Remarks.</td>
                    </tr>
                    <!-- DISPOSE -->
                    <tr>
                        <td><strong>DISPOSE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>Remarks</td>
                        <td>Used for materials logged out as scrap or waste.</td>
                    </tr>
                    <!-- LOST -->
                    <tr>
                        <td><strong>LOST</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>Remarks</td>
                        <td>Deducts stock levels for missing or damaged items.</td>
                    </tr>
                    <!-- RETURN -->
                    <tr>
                        <td><strong>RETURN</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td>Consumable, Asset, Recoverable</td>
                        <td>PO No., Delivery Receipt, Remarks</td>
                        <td>Used for sending items back to supplier/Central.</td>
                    </tr>
                    <!-- MAINTENANCE -->
                    <tr>
                        <td><strong>MAINTENANCE</strong></td>
                        <td><span class="badge bg-danger">OUT</span></td>
                        <td><span class="text-primary fw-bold">Asset, Recoverable</span></td>
                        <td>Remarks</td>
                        <td>Logs out items temporarily for service. <span class="text-danger fw-bold">No Consumables allowed.</span></td>
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
