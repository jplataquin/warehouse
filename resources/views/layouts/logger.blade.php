@extends('layouts.app')

@push('styles')
<style>
    /* Hide the default navbar when in logger view */
    #app > nav.navbar {
        display: none !important;
    }
    body {
        padding-top: 0 !important;
    }
    /* Ensure alerts are not covered by the fixed-top navbar */
    #app > main > .container .alert {
        margin-top: 60px;
    }
</style>
@endpush

@section('content')
{{-- Logger Fixed Top Nav --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top" style="z-index: 1050; min-height: 60px;">
    <div class="container-fluid px-4">
        <!-- Brand -->
        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center me-3">
            <i class="bi bi-person-badge text-primary me-2 fs-5"></i>
            <span class="text-white fw-bold">
                @if(Auth::user()->isAdmin())
                    ADMIN CONSOLE
                @elseif(Auth::user()->isSupervisor())
                    SUPERVISOR CONSOLE
                @elseif(Auth::user()->isViewer())
                    VIEWER CONSOLE
                @else
                    LOGGER CONSOLE
                @endif
            </span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#loggerNavbarContent" aria-controls="loggerNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Content -->
        <div class="collapse navbar-collapse" id="loggerNavbarContent">
            <!-- Left Side Links & Search -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                @unless(Auth::user()->isAdmin() || Auth::user()->isSupervisor())
                <li class="nav-item">
                    <form class="d-flex my-2 my-lg-0" action="{{ route('global.search') }}" method="GET">
                        <div class="input-group input-group-sm" style="max-width: 350px;">
                            <span class="input-group-text bg-secondary border-secondary text-light">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="query" class="form-control bg-secondary border-secondary text-white" placeholder="Search Item, PO, DR, OR or Plate No..." value="{{ request('query') }}">
                        </div>
                    </form>
                </li>
                @endunless

                @if(Auth::user()->isAdmin() || Auth::user()->isSupervisor())
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('ledgers.index') }}">Ledger</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('projects.index') }}">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('warehouses.index') }}">Warehouses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('assignments.index') }}">Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('items.index') }}">Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('items.assets') }}">Assets</a>
                    </li>
                    @if(Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link text-white-50 small fw-bold text-uppercase p-2 d-flex align-items-center gap-1" href="{{ route('admin.items.review') }}">
                                Pending Items
                                @php
                                    $pendingCount = \App\Models\Item::where('is_approved', false)->count();
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="badge bg-danger rounded-pill" style="font-size: 0.7rem; padding: 0.25em 0.5em;">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('users.index') }}">Users</a>
                        </li>
                    @endif
                @endif
                @if(Auth::user()->isLogger() || Auth::user()->isViewer())
                    <li class="nav-item">
                        <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('items.assets') }}">Assets</a>
                    </li>
                @endif
            </ul>

            <!-- Right Side User & Badges -->
            <div class="navbar-nav ms-auto align-items-lg-center gap-2">
                @if(isset($warehouse) && (Auth::user()->isLogger() || Auth::user()->isViewer()))
                    <a href="{{ route('logger.warehouse.dashboard', $warehouse) }}" class="badge bg-primary py-2 px-3 text-decoration-none hover-opacity text-white">
                        <i class="bi bi-geo-alt-fill me-1"></i> {{ $warehouse->name }}
                    </a>
                @endif
                
                <div class="nav-item dropdown">
                    <a id="loggerDropdown" class="nav-link dropdown-toggle text-white small" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        {{ Auth::user()->name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="loggerDropdown">
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault();
                                         document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i> {{ __('Logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4" style="margin-top: 80px;">
    <div class="row">
        @if(Auth::check() && (Auth::user()->role === 'logger' || Auth::user()->role === 'viewer'))
            <div class="col-md-3 mb-4">
                <div class="sticky-top" style="top: 80px; max-height: calc(100vh - 100px); overflow-y: auto;">
                    @php
                        $isSearch = request()->routeIs('global.search');
                        $activeId = !$isSearch ? ($warehouse->id ?? $selectedWarehouseId ?? request('warehouse_id') ?? ($ledger->warehouse_id ?? null)) : null;
                        
                        // If the active warehouse is a sub-warehouse, resolve its parent ID to keep parent active in sidebar
                        if ($activeId) {
                            $whModel = \App\Models\Warehouse::find($activeId);
                            if ($whModel && $whModel->parent_id) {
                                $activeId = $whModel->parent_id;
                            }
                        }
                        
                        $activeWarehouse = $activeId ? (object)['id' => (int)$activeId] : null;
                    @endphp
                    @include('logger.partials.sidebar', ['warehouse' => $activeWarehouse])
                </div>
            </div>
            <div class="col-md-9">
                @yield('inner_content')
            </div>
        @else
            <div class="col-md-12">
                @yield('inner_content')
            </div>
        @endif
    </div>
</div>
@endsection
