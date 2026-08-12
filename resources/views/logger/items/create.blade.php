@extends('layouts.logger')

@section('inner_content')
<div class="container">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-1"></i> Create New Item (For Review)</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
                <div>
                    <strong>Notice:</strong> Any item you create as a Logger will be flagged for administrator review. You can immediately log warehouse transactions using this item once created.
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('logger.items.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($warehouseId)
                    <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                @endif

                <div class="mb-3">
                    <label class="form-label fw-bold">Item Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', request('name')) }}" placeholder="e.g. Copper Cable, Cement" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select" required>
                        @foreach(\App\Models\ItemType::all() as $itemType)
                            <option value="{{ $itemType->id }}" data-behavior="{{ $itemType->base_behavior }}" {{ old('type', request('type')) == $itemType->id ? 'selected' : '' }}>
                                {{ strtoupper($itemType->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" id="status-group" style="display: none;">
                    <label class="form-label fw-bold">Status (Assets Only)</label>
                    <select name="status" class="form-select">
                        <option value="Operational" {{ old('status', request('status')) === 'Operational' ? 'selected' : '' }}>Operational</option>
                        <option value="Out of Order" {{ old('status', request('status')) === 'Out of Order' ? 'selected' : '' }}>Out of Order</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Specification</label>
                    <input type="text" name="specification" class="form-control" value="{{ old('specification', request('specification')) }}" placeholder="e.g. 10m, 50kg, 1/2 inch (Optional)">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Unit of Measure</label>
                    <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', request('unit')) }}" placeholder="e.g. pcs, bags, rolls, meters" required>
                    @error('unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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

                <div class="d-flex gap-2">
                    <button type="submit" id="btn-submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Create Item
                    </button>
                    @if($warehouseId)
                        <a href="{{ route('logger.warehouse.dashboard', $warehouseId) }}" class="btn btn-secondary">Cancel</a>
                    @else
                        <a href="{{ route('home') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const statusGroup = document.getElementById('status-group');

    function toggleStatusField() {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const behavior = selectedOption ? selectedOption.getAttribute('data-behavior') : '';
        if (behavior === 'ASSET') {
            statusGroup.style.display = 'block';
        } else {
            statusGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', toggleStatusField);
    toggleStatusField(); // Run on load

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
