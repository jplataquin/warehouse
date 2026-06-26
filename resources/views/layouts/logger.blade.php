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
<nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm fixed-top" style="z-index: 1050; height: 60px;">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center w-100">
            <div class="d-flex align-items-center me-auto">
                <a href="{{ route('home') }}" class="text-decoration-none d-flex align-items-center me-4">
                    <i class="bi bi-person-badge text-primary me-2 fs-5"></i>
                    <span class="text-white fw-bold">
                        @if(Auth::user()->isAdmin())
                            ADMIN CONSOLE
                        @elseif(Auth::user()->isSupervisor())
                            SUPERVISOR CONSOLE
                        @else
                            LOGGER CONSOLE
                        @endif
                    </span>
                </a>
                
                @unless(Auth::user()->isAdmin() || Auth::user()->isSupervisor())
                <form class="d-none d-md-flex" action="{{ route('global.search') }}" method="GET">
                    <div class="input-group input-group-sm" style="width: 350px;">
                        <span class="input-group-text bg-secondary border-secondary text-light">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="query" class="form-control bg-secondary border-secondary text-white" placeholder="Search Item, PO, DR, OR or Plate No..." value="{{ request('query') }}">
                    </div>
                </form>
                @endunless

                @if(Auth::user()->isAdmin() || Auth::user()->isSupervisor())
                    <ul class="navbar-nav ms-4 d-none d-lg-flex">
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
                                <a class="nav-link text-white-50 small fw-bold text-uppercase p-2" href="{{ route('users.index') }}">Users</a>
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
            
            <div class="ms-auto d-flex align-items-center">
                @if(isset($warehouse) && Auth::user()->isLogger())
                    <a href="{{ route('logger.warehouse.dashboard', $warehouse) }}" class="badge bg-primary me-3 text-decoration-none hover-opacity">
                        <i class="bi bi-geo-alt-fill me-1"></i> {{ $warehouse->name }}
                    </a>
                @endif
                
                <div class="dropdown">
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
        @if(Auth::check() && Auth::user()->role === 'logger')
            <div class="col-md-3 mb-4">
                <div class="sticky-top" style="top: 80px; max-height: calc(100vh - 100px); overflow-y: auto;">
                    @php
                        $isSearch = request()->routeIs('global.search');
                        $activeId = !$isSearch ? ($warehouse->id ?? $selectedWarehouseId ?? request('warehouse_id') ?? ($ledger->warehouse_id ?? null)) : null;
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
