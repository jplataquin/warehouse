@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold text-secondary">
                <i class="bi bi-gear-fill me-2 text-primary"></i>System Settings
            </h2>
            <p class="text-muted">Configure and manage various aspects of the warehouse application.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- API Configuration Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                            <i class="bi bi-key-fill fs-4"></i>
                        </div>
                        <h4 class="card-title mb-0 fw-bold">API Settings</h4>
                    </div>
                    <p class="card-text text-muted flex-grow-1">
                        Manage API credentials, base URLs, and access keys for external services like MQMS integration.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('api-credentials.index') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-gear"></i> Configure APIs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management Card (Placeholder for completeness) -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm opacity-75">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-secondary-subtle text-secondary p-3 rounded-3 me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <h4 class="card-title mb-0 fw-bold">Users & Roles</h4>
                    </div>
                    <p class="card-text text-muted flex-grow-1">
                        Configure system users, assign roles (Admin, Supervisor, Logger, Viewer), and manage access permissions.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-person-gear"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Item Types Management Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                            <i class="bi bi-tags-fill fs-4"></i>
                        </div>
                        <h4 class="card-title mb-0 fw-bold">Item Types</h4>
                    </div>
                    <p class="card-text text-muted flex-grow-1">
                        Create and manage custom item categories, specifying if they behave as consumable bulk goods or unique assets.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('item-types.index') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-gear"></i> Configure Types
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .transition-all {
        transition: all 0.3s ease-in-out;
    }
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08)!important;
    }
    .bg-primary-subtle {
        background-color: #cfe2ff !important;
    }
    .bg-secondary-subtle {
        background-color: #e2e3e5 !important;
    }
    .bg-dark-subtle {
        background-color: #d3d3d4 !important;
    }
</style>
@endsection
