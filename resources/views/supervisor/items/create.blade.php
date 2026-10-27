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
                        @foreach(\App\Models\ItemType::all() as $itemType)
                            <option value="{{ $itemType->id }}" data-behavior="{{ $itemType->base_behavior }}" {{ old('type') == $itemType->id ? 'selected' : '' }}>
                                {{ strtoupper($itemType->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" selected>Operational</option>
                        <option value="Out of Order">Out of Order</option>
                    </select>
                </div>
                <div class="mb-3" id="category-group" style="display: none;">
                    <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" id="category_search" class="form-control @error('category_id') is-invalid @enderror" placeholder="Type to search or select category..." list="category-options" autocomplete="off" value="{{ old('category_name') }}">
                        <button class="btn btn-outline-secondary" type="button" id="clear-category-btn" title="Clear selection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <datalist id="category-options">
                        @foreach($categories as $category)
                            <option value="{{ $category->name }}" data-id="{{ $category->id }}"></option>
                        @endforeach
                    </datalist>
                    <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id') }}">
                    @error('category_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                        <div class="form-text">Required for Asset item types. Select from the dropdown or type to filter.</div>
                    @enderror
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
                        <div class="col-md-6">
                            <!-- Clickable Dropzone -->
                            <div id="photo-dropzone" class="border rounded bg-light p-3 d-flex flex-column justify-content-center align-items-center mb-2 h-100" style="min-height: 220px; border: 2px dashed #0d6efd !important; transition: all 0.2s ease-in-out; cursor: pointer;" title="Click to select file, or drag and drop or paste image here">
                                <img id="photo-preview" class="img-fluid rounded shadow-sm mb-2" style="max-height: 160px; display: none;" alt="Preview">
                                <div id="photo-placeholder" class="text-center text-muted p-2">
                                    <i class="bi bi-cloud-arrow-up-fill fs-1 d-block mb-2 text-primary"></i>
                                    <span class="fw-bold d-block small">Click to upload or drag image here</span>
                                    <span class="small text-muted d-block mt-1" style="font-size: 0.75rem;">You can also copy & paste an image directly (Ctrl+V)</span>
                                </div>
                                <div id="upload-progress-container" class="w-100 px-3 mt-2 d-none">
                                    <div class="progress mb-1" style="height: 8px;">
                                        <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <span id="upload-progress-text" class="small text-muted d-block text-center" style="font-size: 0.7rem;">Uploading... 0%</span>
                                </div>
                            </div>
                            
                            <!-- Hidden native file input and temp file name -->
                            <input type="file" id="photo_file" accept=".jpeg,.jpg,.png,image/jpeg,image/png" capture="environment" class="d-none">
                            <input type="hidden" name="temp_photo_file" id="temp_photo_file" value="{{ old('temp_photo_file') }}">
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const statusGroup = document.getElementById('status-group');
    const categoryGroup = document.getElementById('category-group');
    const categorySearch = document.getElementById('category_search');
    const categoryIdInput = document.getElementById('category_id');
    const categoryDatalist = document.getElementById('category-options');
    const clearCategoryBtn = document.getElementById('clear-category-btn');

    function toggleAssetFields() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const behavior = selectedOption ? selectedOption.getAttribute('data-behavior') : '';
        if (behavior === 'ASSET') {
            statusGroup.style.display = 'block';
            categoryGroup.style.display = 'block';
            categorySearch.required = true;
        } else {
            statusGroup.style.display = 'none';
            categoryGroup.style.display = 'none';
            categorySearch.required = false;
        }
    }

    typeSelect.addEventListener('change', toggleAssetFields);
    toggleAssetFields(); // Run on load

    function syncCategory() {
        const val = categorySearch.value.trim().toLowerCase();
        let matchedId = '';
        if (val) {
            for (let option of categoryDatalist.options) {
                if (option.value.toLowerCase() === val) {
                    matchedId = option.getAttribute('data-id');
                    break;
                }
            }
        }
        categoryIdInput.value = matchedId;
    }

    categorySearch.addEventListener('input', syncCategory);
    categorySearch.addEventListener('change', syncCategory);

    if (clearCategoryBtn) {
        clearCategoryBtn.addEventListener('click', function() {
            categorySearch.value = '';
            categoryIdInput.value = '';
            categorySearch.focus();
        });
    }

    // Pre-populate categorySearch if category_id exists (e.g. from validation failure)
    if (categoryIdInput.value && !categorySearch.value) {
        for (let option of categoryDatalist.options) {
            if (option.getAttribute('data-id') === categoryIdInput.value) {
                categorySearch.value = option.value;
                break;
            }
        }
    }

    // Photo Preview & Search Logic
    const photoFileInput = document.getElementById('photo_file');
    const photoPreview = document.getElementById('photo-preview');
    const photoPlaceholder = document.getElementById('photo-placeholder');
    const photoDropzone = document.getElementById('photo-dropzone');
    const tempPhotoFileInput = document.getElementById('temp_photo_file');

    // Restore temp preview on load if present
    if (tempPhotoFileInput && tempPhotoFileInput.value) {
        photoPreview.src = `/temp-preview/${encodeURIComponent(tempPhotoFileInput.value)}`;
        photoPreview.style.display = 'block';
        photoPlaceholder.style.display = 'none';
    }

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

    function uploadFileInChunks(file) {
        // Validate file type
        const allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
        const allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedExtensions.exec(file.name) || (file.type && !allowedMimeTypes.includes(file.type))) {
            alert('Error: Only jpeg, jpg, and png formats are allowed.');
            photoFileInput.value = '';
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Error: File size cannot exceed 5MB.');
            photoFileInput.value = '';
            return;
        }

        const submitButton = document.getElementById('btn-submit');
        let originalSubmitHtml = '';
        if (submitButton) {
            originalSubmitHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Uploading Image...`;
        }

        const chunkSize = 256 * 1024; // 256KB chunks
        const totalChunks = Math.ceil(file.size / chunkSize);
        const fileId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substring(2, 11);
        const fileName = file.name || 'pasted-image.png';

        const progressContainer = document.getElementById('upload-progress-container');
        const progressBar = document.getElementById('upload-progress-bar');
        const progressText = document.getElementById('upload-progress-text');

        progressContainer.classList.remove('d-none');
        photoPlaceholder.style.display = 'none';
        photoPreview.style.display = 'none';

        let currentChunk = 0;

        function uploadNextChunk() {
            const start = currentChunk * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunkBlob = file.slice(start, end);

            const formData = new FormData();
            formData.append('file_id', fileId);
            formData.append('chunk_index', currentChunk);
            formData.append('total_chunks', totalChunks);
            formData.append('file_name', fileName);
            formData.append('file_chunk', chunkBlob, fileName);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '{{ route("chunk.upload") }}', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const overallProgress = Math.round(((currentChunk * chunkSize) + (e.loaded)) / file.size * 100);
                    const safeProgress = Math.min(overallProgress, 99); // Show 99% until completely merged
                    progressBar.style.width = safeProgress + '%';
                    progressText.textContent = `Uploading... ${safeProgress}%`;
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.temp_file_name) {
                        progressBar.style.width = '100%';
                        progressText.textContent = 'Upload complete!';
                        setTimeout(() => {
                            progressContainer.classList.add('d-none');
                            
                            // Set hidden input with temp filename
                            tempPhotoFileInput.value = response.temp_file_name;

                            // Re-enable submit button
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalSubmitHtml;
                            }

                            // Render local preview of the completed file
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                photoPreview.src = e.target.result;
                                photoPreview.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        }, 500);
                    } else {
                        currentChunk++;
                        if (currentChunk < totalChunks) {
                            uploadNextChunk();
                        }
                    }
                } else {
                    alert('Chunk upload failed. Please try again.');
                    progressContainer.classList.add('d-none');
                    photoPlaceholder.style.display = 'block';
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalSubmitHtml;
                    }
                }
            };

            xhr.onerror = function() {
                alert('Upload connection error. Please try again.');
                progressContainer.classList.add('d-none');
                photoPlaceholder.style.display = 'block';
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalSubmitHtml;
                }
            };

            xhr.send(formData);
        }

        uploadNextChunk();
    }

    // Handle Local File Upload
    photoFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            uploadFileInChunks(this.files[0]);
        }
    });
});
</script>
@endsection
