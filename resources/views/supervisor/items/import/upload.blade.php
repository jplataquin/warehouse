@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-excel me-2"></i> Bulk Item Import</h5>
                    <a href="{{ route('items.import.template') }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i> Download Template
                    </a>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('items.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4 text-center p-5 border border-dashed rounded-3 bg-light">
                            <i class="bi bi-cloud-upload fs-1 text-secondary mb-3 d-block"></i>
                            <label for="file" class="form-label fw-bold">Select Excel or CSV File</label>
                            <input type="file" name="file" id="file" class="form-control" required accept=".xlsx,.xls,.csv">
                            <div class="form-text mt-2 small">Supported formats: .xlsx, .xls, .csv</div>
                        </div>

                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <h6 class="fw-bold"><i class="bi bi-info-circle me-1"></i> File Requirements:</h6>
                            <ul class="small mb-0">
                                <li>The first row must be the heading row.</li>
                                <li>Required Columns: <strong>type</strong>, <strong>name</strong>, <strong>specification</strong>, <strong>unit</strong></li>
                                <li>Valid Types: <code>CONSUMABLE</code>, <code>ASSET</code></li>
                                <li>Uniqueness: The combination of <strong>name</strong>, <strong>specification</strong>, and <strong>unit</strong> must be unique.</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Upload and Review</button>
                        </div>
                    </form>
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
</style>
@endsection
