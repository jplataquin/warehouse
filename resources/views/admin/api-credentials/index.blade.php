@extends('layouts.app')

@block('content')
@endblock

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold text-secondary"><i class="bi bi-key-fill me-2 text-primary"></i>API Credentials</h2>
                <a href="{{ route('api-credentials.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> Add API Credential
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase fs-7 text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Service</th>
                                    <th class="py-3">Base URL</th>
                                    <th class="py-3">API Key</th>
                                    <th class="py-3">Secret Key</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="pe-4 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($credentials as $credential)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            {{ strtoupper($credential->service) }}
                                        </td>
                                        <td>
                                            <code class="text-secondary">{{ $credential->base_url ?: 'N/A' }}</code>
                                        </td>
                                        <td>
                                            <code>{{ $credential->api_key ?: 'N/A' }}</code>
                                        </td>
                                        <td>
                                            @if ($credential->secret_key)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Configured</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Not Configured</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($credential->is_active)
                                                <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                            @else
                                                <span class="badge bg-secondary rounded-pill px-3 py-2">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('api-credentials.edit', $credential) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <form action="{{ route('api-credentials.destroy', $credential) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this API credential?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-shield-lock fs-1 d-block mb-3 text-secondary"></i>
                                            No API credentials configured. Add one to get started.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
