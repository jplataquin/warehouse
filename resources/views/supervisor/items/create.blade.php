@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card">
        <div class="card-header">New Item</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="CONSUMABLE">CONSUMABLE</option>
                        <option value="ASSET">ASSET</option>
                        
                    </select>
                </div>
                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" selected>Operational</option>
                        <option value="Out of Order">Out of Order</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specification</label>
                    <input type="text" name="specification" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" required>
                </div>

                <div class="mb-4 border-top pt-4">
                    <h5 class="fw-bold mb-3 text-secondary text-uppercase fs-6">Item Photo (Optional)</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <div id="photo-dropzone" class="border rounded bg-light p-3 d-flex flex-column justify-content-center align-items-center mb-2" style="min-height: 200px; border: 2px dashed #dee2e6 !important; transition: all 0.2s ease-in-out; cursor: pointer;">
                                <img id="photo-preview" class="img-fluid rounded shadow-sm" style="max-height: 180px; display: none;" alt="Preview">
                                <div id="photo-placeholder" class="text-muted">
                                    <i class="bi bi-image fs-1 d-block mb-2"></i>
                                    <span>Drag & drop or paste photo here</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <!-- Device Upload / Take Picture -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Upload / Take Picture</label>
                                <input type="file" name="photo_file" id="photo_file" accept="image/*" capture="environment" class="form-control">
                                <div class="form-text small">Works with device camera on mobile, or file selector on desktop.</div>
                            </div>

                            <!-- Google Image Search -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Search Web for a Photo</label>
                                <div class="input-group">
                                    <input type="text" id="web-search-query" class="form-control" placeholder="Enter term (e.g. DeWalt Drill DCD771)">
                                    <button class="btn btn-outline-secondary" type="button" id="btn-web-search">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <div id="search-results" class="row row-cols-4 g-2 mt-2" style="max-height: 200px; overflow-y: auto;">
                                    <!-- Search results populated by JS -->
                                </div>
                                <input type="hidden" name="photo_url" id="photo_url">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const statusGroup = document.getElementById('status-group');

    function toggleStatusField() {
        if (typeSelect.value === 'ASSET') {
            statusGroup.style.display = 'block';
        } else {
            statusGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', toggleStatusField);
    toggleStatusField(); // Run on load

    // Photo Preview & Search Logic
    const photoFileInput = document.getElementById('photo_file');
    const photoUrlInput = document.getElementById('photo_url');
    const photoPreview = document.getElementById('photo-preview');
    const photoPlaceholder = document.getElementById('photo-placeholder');
    const photoDropzone = document.getElementById('photo-dropzone');
    const webSearchQueryInput = document.getElementById('web-search-query');
    const btnWebSearch = document.getElementById('btn-web-search');
    const searchResultsContainer = document.getElementById('search-results');

    // Drag & Drop event handling
    if (photoDropzone) {
        photoDropzone.addEventListener('click', function() {
            photoFileInput.click();
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            photoDropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                photoDropzone.classList.add('bg-primary-subtle', 'border-primary');
                photoDropzone.style.borderColor = '#0d6efd';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            photoDropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                photoDropzone.classList.remove('bg-primary-subtle', 'border-primary');
                photoDropzone.style.borderColor = '#dee2e6';
            }, false);
        });

        photoDropzone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                const validFiles = Array.from(files).filter(f => f.type.startsWith('image/'));
                if (validFiles.length > 0) {
                    const container = new DataTransfer();
                    container.items.add(validFiles[0]);
                    photoFileInput.files = container.files;
                    
                    const event = new Event('change', { bubbles: true });
                    photoFileInput.dispatchEvent(event);
                }
            }
        }, false);
    }

    // Copy & Paste event handling
    document.addEventListener('paste', function(e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let index in items) {
            const item = items[index];
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                const blob = item.getAsFile();
                const file = new File([blob], "pasted-image.png", { type: item.type });
                
                const container = new DataTransfer();
                container.items.add(file);
                photoFileInput.files = container.files;
                
                const event = new Event('change', { bubbles: true });
                photoFileInput.dispatchEvent(event);
                
                break;
            }
        }
    });

    // Handle Local File Upload Preview
    photoFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                photoPlaceholder.style.display = 'none';
            }
            reader.readAsDataURL(this.files[0]);
            
            photoUrlInput.value = '';
            document.querySelectorAll('.search-thumb-container').forEach(el => el.classList.remove('border', 'border-primary', 'border-3'));
        }
    });

    // Trigger search on Enter key press
    webSearchQueryInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            btnWebSearch.click();
        }
    });

    // Handle Web Search
    btnWebSearch.addEventListener('click', function() {
        const query = webSearchQueryInput.value.trim();
        if (!query) {
            alert('Please enter a search term first.');
            return;
        }

        btnWebSearch.disabled = true;
        btnWebSearch.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        searchResultsContainer.innerHTML = '';

        fetch(`{{ route('items.search-images') }}?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.error || 'Failed to fetch search results.'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.length === 0) {
                    searchResultsContainer.innerHTML = '<div class="col-12 text-muted text-center py-2">No images found.</div>';
                } else {
                    data.forEach(item => {
                        const col = document.createElement('div');
                        col.className = 'col text-center';
                        col.innerHTML = `
                            <div class="card h-100 p-1 search-thumb-container" style="cursor: pointer;">
                                <img src="${item.thumbnail}" data-full="${item.link}" class="img-fluid rounded search-thumb" style="max-height: 80px; object-fit: cover;" title="${item.title}">
                            </div>
                        `;
                        col.querySelector('.search-thumb-container').addEventListener('click', function() {
                            document.querySelectorAll('.search-thumb-container').forEach(el => el.classList.remove('border', 'border-primary', 'border-3'));
                            this.classList.add('border', 'border-primary', 'border-3');
                            
                            photoUrlInput.value = item.link;
                            
                            photoPreview.src = item.thumbnail;
                            photoPreview.style.display = 'block';
                            photoPlaceholder.style.display = 'none';
                            
                            photoFileInput.value = '';
                        });
                        searchResultsContainer.appendChild(col);
                    });
                }
            })
            .catch(error => {
                alert(error.message);
                searchResultsContainer.innerHTML = `<div class="col-12 text-danger text-center py-2">${error.message}</div>`;
            })
            .finally(() => {
                btnWebSearch.disabled = false;
                btnWebSearch.innerHTML = '<i class="bi bi-search"></i> Search';
            });
    });
});
</script>
@endsection
