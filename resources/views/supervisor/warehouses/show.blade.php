@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <!-- Information Section -->
            <div class="card mb-4">
                <div class="card-header">Warehouse Information</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Name:</div>
                        <div class="col-sm-9">{{ $warehouse->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Type:</div>
                        <div class="col-sm-9">{{ $warehouse->type }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Project:</div>
                        <div class="col-sm-9">{{ $warehouse->project ? $warehouse->project->name : 'N/A' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Status:</div>
                        <div class="col-sm-9">{{ $warehouse->status }}</div>
                    </div>
                </div>
            </div>

            <!-- List of Assigned Loggers Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Assigned Loggers</span>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addLoggerForm">
                        Add Logger
                    </button>
                </div>
                <div class="card-body">
                    <div id="addLoggerForm" class="collapse mb-4">
                        <form action="{{ route('warehouses.loggers.assign', $warehouse) }}" method="POST">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="logger_search" id="logger_search" class="form-control" placeholder="Search logger name..." list="loggerSuggestions" autocomplete="off">
                                <datalist id="loggerSuggestions">
                                    @foreach($availableLoggers as $logger)
                                        <option value="{{ $logger->name }}" data-id="{{ $logger->id }}"></option>
                                    @endforeach
                                </datalist>
                                <input type="hidden" name="logger_id" id="logger_id">
                                <button class="btn btn-success" type="submit" id="addLoggerBtn" disabled>Add</button>
                            </div>
                            <small class="text-muted">Type a name and select from the suggestions.</small>
                        </form>
                    </div>

                    @if($warehouse->loggers->isEmpty())
                        <p class="text-muted mb-0">No loggers assigned to this warehouse.</p>
                    @else
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouse->loggers as $logger)
                                    <tr>
                                        <td>{{ $logger->name }}</td>
                                        <td>{{ $logger->email }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('warehouses.loggers.remove', [$warehouse, $logger]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this logger?')">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <!-- Allocations Section -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i> Current Allocations</h5>
                    <div class="btn-group">
                        @if($warehouse->type === 'SITE' && $warehouse->project && $warehouse->project->mapped_to_project_id)
                            <button type="button" class="btn btn-sm btn-outline-primary me-3" data-bs-toggle="modal" data-bs-target="#importMqmsModal">
                                <i class="bi bi-cloud-download me-1"></i> Import Components
                            </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#addAllocationForm">
                            <i class="bi bi-plus-circle me-1"></i> Add Allocation
                        </button>
                    </div>
                </div>

                <!-- MQMS Import Modal -->
                @if($warehouse->type === 'SITE' && $warehouse->project && $warehouse->project->mapped_to_project_id)
                <div class="modal fade" id="importMqmsModal" tabindex="-1" aria-labelledby="importMqmsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('warehouses.import-components.preview', $warehouse) }}" method="GET">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="importMqmsModalLabel">Import Components from MQMS</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Select a section from <strong>{{ $warehouse->project->name }}</strong> to fetch approved components.</p>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Select Section</label>
                                        <select name="section_id" id="mqmsSectionSelect" class="form-select" required disabled>
                                            <option value="">Loading sections...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="mqmsPreviewBtn" disabled>Preview Components</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card-body p-0">
                    <div id="addAllocationForm" class="collapse p-3 border-bottom bg-light">
                        <form action="{{ route('allocations.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                            <div class="row align-items-end">
                                <div class="col">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Allocation Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Phase 1 Electrical" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-success">Save Allocation</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Allocation Name</th>
                                    <th>MQMS ID</th>
                                    <th>Created At</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouse->allocations as $allocation)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">{{ $allocation->name }}</td>
                                        <td>
                                            @if($allocation->mapped_to_component_id)
                                                <code class="small">{{ $allocation->mapped_to_component_id }}</code>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $allocation->created_at->format('M d, Y') }}</td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="{{ route('allocations.edit', $allocation) }}" class="btn btn-sm btn-outline-secondary border-0">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('allocations.destroy', $allocation) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Are you sure you want to delete this allocation?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
                                            No allocations found for this warehouse.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Public Dashboard Link Section -->
            <div class="card mb-4">
                <div class="card-header">Public Stock Dashboard</div>
                <div class="card-body">
                    @if($warehouse->public_token)
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Public Dashboard URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="publicUrlInput" value="{{ route('public.warehouse.dashboard', $warehouse->public_token) }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyPublicUrl()"><i class="bi bi-copy"></i></button>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('public.warehouse.dashboard', $warehouse->public_token) }}" class="btn btn-outline-primary btn-sm flex-grow-1" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Dashboard
                            </a>
                            <form action="{{ route('warehouses.public_token.revoke', $warehouse) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Are you sure you want to revoke this public access link?')">
                                    <i class="bi bi-x-circle me-1"></i> Revoke
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="small text-muted mb-3">No public link generated. Create one to allow read-only real-time stock view without an account.</p>
                        <form action="{{ route('warehouses.public_token.generate', $warehouse) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-link-45deg me-1"></i> Generate Public Link
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Action Buttons Section -->
            <div class="card mb-4">
                <div class="card-header">Actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-warning">Edit Warehouse</a>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Back to List</a>
                    
                    <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this warehouse?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">Delete Warehouse</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">About Allocations</h6>
                    <p class="small text-muted mb-0">
                        Allocations allow you to track item movements toward specific targets within this warehouse. Loggers select these when performing OUT movements or specific IN movements for consumable items.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.copyPublicUrl = function() {
        const publicUrlInput = document.getElementById('publicUrlInput');
        if (publicUrlInput) {
            publicUrlInput.select();
            publicUrlInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(publicUrlInput.value)
                .then(() => {
                    alert('Public URL copied to clipboard!');
                })
                .catch(err => {
                    console.error('Error copying text: ', err);
                });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const loggerSearch = document.getElementById('logger_search');
        const loggerIdInput = document.getElementById('logger_id');
        const addLoggerBtn = document.getElementById('addLoggerBtn');
        const datalist = document.getElementById('loggerSuggestions');

        if (loggerSearch) {
            loggerSearch.addEventListener('input', function() {
                const val = this.value;
                const options = datalist.options;
                let found = false;

                for (let i = 0; i < options.length; i++) {
                    if (options[i].value === val) {
                        loggerIdInput.value = options[i].getAttribute('data-id');
                        found = true;
                        break;
                    }
                }

                if (found) {
                    addLoggerBtn.disabled = false;
                } else {
                    loggerIdInput.value = '';
                    addLoggerBtn.disabled = true;
                }
            });
        }

        // MQMS Sections Loading
        const importModal = document.getElementById('importMqmsModal');
        if (importModal) {
            const sectionSelect = document.getElementById('mqmsSectionSelect');
            const previewBtn = document.getElementById('mqmsPreviewBtn');
            let sectionsLoaded = false;

            importModal.addEventListener('show.bs.modal', function() {
                if (sectionsLoaded) return;

                fetch("{{ route('warehouses.import-components.sections', $warehouse) }}")
                    .then(response => response.json())
                    .then(data => {
                        sectionSelect.innerHTML = '<option value="">Choose a section...</option>';
                        if (data.error) {
                            sectionSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                        } else {
                            data.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.name;
                                sectionSelect.appendChild(option);
                            });
                            sectionSelect.disabled = false;
                            previewBtn.disabled = false;
                            sectionsLoaded = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading MQMS sections:', error);
                        sectionSelect.innerHTML = '<option value="">Failed to load sections</option>';
                    });
            });
        }
    });
</script>
@endsection
