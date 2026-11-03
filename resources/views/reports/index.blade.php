@extends('layouts.logger')

@section('inner_content')
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

                <!-- Unreturned Assets Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-primary">
                                <i class="bi bi-clock-history me-2"></i>Unreturned Assets
                            </h5>
                            <p class="card-text text-muted flex-grow-1">
                                Track all assets currently logged out for utilization or maintenance and see how many days they have been out of any warehouse.
                            </p>
                            <a href="{{ route('reports.unreturned-assets') }}" class="btn btn-primary mt-3">
                                View Report <i class="bi bi-arrow-right ms-1"></i>
                            </a>
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
