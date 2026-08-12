@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="{{ route('api-credentials.index') }}" class="btn btn-outline-secondary btn-sm rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="mb-0 fw-bold text-secondary">Add API Credential</h2>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('api-credentials.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="service" class="form-label fw-bold">Service Identifier</label>
                            <input type="text" class="form-control @error('service') is-invalid @enderror" id="service" name="service" value="{{ old('service') }}" placeholder="e.g., mqms" required>
                            <div class="form-text">A unique, lowercase name to identify this API (e.g., 'mqms').</div>
                            @error('service')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="base_url" class="form-label fw-bold">Base URL</label>
                            <input type="text" class="form-control @error('base_url') is-invalid @enderror" id="base_url" name="base_url" value="{{ old('base_url') }}" placeholder="e.g., https://api.mqms.example.com">
                            @error('base_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="api_key" class="form-label fw-bold">API Key</label>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror" id="api_key" name="api_key" value="{{ old('api_key') }}">
                            @error('api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="secret_key" class="form-label fw-bold">Secret Key</label>
                            <input type="password" class="form-control @error('secret_key') is-invalid @enderror" id="secret_key" name="secret_key">
                            <div class="form-text">This will be securely encrypted using AES-256 before being stored in the database.</div>
                            @error('secret_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-bold" for="is_active">Activate Credential</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-4">
                            <a href="{{ route('api-credentials.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Create Credential</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
