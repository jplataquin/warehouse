@extends('layouts.logger')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Reports</h1>
            </div>

            <div class="row">
                <!-- Project Allocation Summary Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-primary">
                                <i class="bi bi-diagram-3 me-2"></i>Project Allocation Summary
                            </h5>
                            <p class="card-text text-muted flex-grow-1">
                                View a tabular summary of the total quantity of items allocated to each allocation target within a selected project and date range.
                            </p>
                            <a href="{{ route('reports.project-allocation-summary') }}" class="btn btn-primary mt-3">
                                View Report <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Future Reports Placeholder Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-dashed bg-light">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-5">
                            <i class="bi bi-plus-circle text-muted fs-1 mb-3"></i>
                            <h5 class="card-title fw-bold text-muted">More Reports Coming Soon</h5>
                            <p class="card-text text-muted max-width-300">
                                Additional analytics and inventory reports will be added here in the future.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed {
        border-style: dashed !important;
        border-width: 2px !important;
    }
    .max-width-300 {
        max-width: 300px;
    }
</style>
@endsection
