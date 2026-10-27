@extends('layouts.logger')

@section('inner_content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0 fw-bold text-secondary">
                        <i class="bi bi-robot me-2 text-primary"></i>MCP Access Tokens
                    </h2>
                    <p class="text-muted small mb-0 mt-1">Manage Bearer tokens used by AI clients to access the warehouse MCP server.</p>
                </div>
                <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-arrow-left"></i> Back to Settings
                </a>
            </div>

            @if(session('new_token'))
                <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                    <h5 class="alert-heading fw-bold d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> Please copy your new MCP Token
                    </h5>
                    <p class="mb-2">This token will <strong>never be shown again</strong>. Please copy it immediately and store it in your AI client configuration along with the endpoint URL: <code>{{ url('/mcp') }}</code>.</p>
                    <div class="input-group">
                        <input type="text" id="plainTextToken" class="form-control font-monospace bg-white" value="{{ session('new_token') }}" readonly>
                        <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('plainTextToken').value); this.innerText = 'Copied!';">
                            <i class="bi bi-clipboard me-1"></i> Copy Token
                        </button>
                    </div>
                </div>
            @endif

            @if(session('success') && !session('new_token'))
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- MCP Server Endpoint Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-link-45deg text-primary fs-4"></i> MCP Server Endpoint
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                            <i class="bi bi-shield-check me-1"></i> Requires Bearer Token
                        </span>
                    </div>
                    <p class="text-muted small mb-3">
                        Connect AI clients (such as Claude Desktop, Cursor, or custom AI agents) to this server using the endpoint URL below with a generated Bearer token.
                    </p>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light text-muted font-monospace"><i class="bi bi-globe me-1"></i> POST</span>
                        <input type="text" id="mcpEndpointUrl" class="form-control font-monospace bg-white" value="{{ url('/mcp') }}" readonly>
                        <button class="btn btn-outline-primary d-flex align-items-center gap-1" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcpEndpointUrl').value); const btn = this; const orig = btn.innerHTML; btn.innerHTML = '<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => btn.innerHTML = orig, 2000);">
                            <i class="bi bi-clipboard"></i> Copy URL
                        </button>
                    </div>

                    <div class="accordion accordion-flush" id="mcpConfigAccordion">
                        <div class="accordion-item border rounded-2">
                            <h2 class="accordion-header" id="headingConfig">
                                <button class="accordion-button collapsed py-2 px-3 small text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConfig" aria-expanded="false" aria-controls="collapseConfig">
                                    <i class="bi bi-code-slash me-2 text-primary"></i> Example AI Client Configuration (JSON)
                                </button>
                            </h2>
                            <div id="collapseConfig" class="accordion-collapse collapse" aria-labelledby="headingConfig" data-bs-parent="#mcpConfigAccordion">
                                <div class="accordion-body bg-light p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">Format for Claude Desktop, Cursor, or MCP clients:</span>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcpConfigJson').innerText); this.innerText = 'Copied!'; setTimeout(() => this.innerText = 'Copy JSON', 2000);">
                                            Copy JSON
                                        </button>
                                    </div>
                                    <pre class="mb-0 p-3 bg-dark text-light rounded-2 small font-monospace"><code id="mcpConfigJson">{
  "mcpServers": {
    "warehouse": {
      "url": "{{ url('/mcp') }}",
      "headers": {
        "Authorization": "Bearer &lt;YOUR_MCP_TOKEN&gt;"
      }
    }
  }
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Token Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-3">Generate New MCP Token</h5>
                    <form action="{{ route('mcp-tokens.store') }}" method="POST" class="row g-3 align-items-center">
                        @csrf
                        <div class="col-md-8">
                            <input type="text" name="token_name" class="form-control @error('token_name') is-invalid @enderror" placeholder="Token name or client (e.g., Claude Desktop, Cursor)" required>
                            @error('token_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-plus-circle"></i> Generate Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Tokens Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase fs-7 text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Token Name</th>
                                    <th class="py-3">Abilities</th>
                                    <th class="py-3">Created At</th>
                                    <th class="py-3">Last Used</th>
                                    <th class="pe-4 py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tokens as $token)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            {{ $token->name }}
                                        </td>
                                        <td>
                                            @foreach($token->abilities as $ability)
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $ability }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $token->created_at->format('M d, Y H:i') }}</span>
                                        </td>
                                        <td>
                                            @if($token->last_used_at)
                                                <span class="text-muted small">{{ $token->last_used_at->diffForHumans() }}</span>
                                            @else
                                                <span class="badge bg-light text-muted border rounded-pill">Never</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form action="{{ route('mcp-tokens.destroy', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to revoke this MCP token? Any client using it will lose access.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-trash"></i> Revoke
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-key fs-1 d-block mb-3 text-secondary"></i>
                                            No MCP tokens generated yet. Generate one above to connect an AI client.
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
