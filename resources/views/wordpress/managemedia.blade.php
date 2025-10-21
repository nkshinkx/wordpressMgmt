@extends('layouts.app')

@section('title', 'Manage WordPress Media')

@section('content')


    @if (!isset($csrfTokenSet))
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <div class="page-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 pb-2">Media Library - {{ $wpSite->site_name }}</h4>
                        <div>
                            <a href="{{ url('wordpress-sites-list') }}" class="btn btn-secondary btn-md me-2">
                                <i class="bx bx-arrow-back"></i> Back to Sites
                            </a>
                            <button class="btn btn-success btn-md" data-bs-toggle="modal"
                                data-bs-target="#uploadMediaModal">
                                <i class="bx bx-cloud-upload"></i> Upload Media
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Filter Bar -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Filter by Type</label>
                                    <select class="form-select" id="mediaTypeFilter" onchange="filterMedia()">
                                        <option value="">All Types</option>
                                        <option value="image">Images</option>
                                        <option value="document">Documents</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Search</label>
                                    <input type="text" class="form-control" id="searchInput"
                                        placeholder="Search by filename..." onkeyup="handleSearch()">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-primary w-100" onclick="filterMedia()">
                                        <i class="bx bx-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Media Files</h5>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary active" onclick="setView('grid')">
                                    <i class="bx bx-grid-alt"></i> Grid
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="setView('list')">
                                    <i class="bx bx-list-ul"></i> List
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="mediaContainer" class="row g-3">
                                <!-- Media items will be loaded here -->
                                <div class="col-12 text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading media...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Media Modal -->
    <div class="modal fade" id="uploadMediaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadMediaForm">
                        <div class="mb-3">
                            <label for="mediaFile" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="mediaFile"
                                accept="image/*,application/pdf,.doc,.docx" multiple>
                            <small class="text-muted">
                                Supported: JPG, PNG, GIF, PDF, DOC, DOCX (Max 10MB each)
                            </small>
                        </div>
                        <div id="uploadProgress" class="mb-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 0%" id="uploadProgressBar"></div>
                            </div>
                            <small class="text-muted mt-2" id="uploadStatus">Preparing upload...</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="uploadMedia()">
                        <i class="bx bx-cloud-upload"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Detail Modal -->
    <div class="modal fade" id="mediaDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaDetailTitle">Media Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="mediaDetailImage" src="" class="img-fluid rounded" alt="Media">
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Filename:</dt>
                                <dd class="col-sm-8" id="mediaDetailFilename"></dd>

                                <dt class="col-sm-4">File Size:</dt>
                                <dd class="col-sm-8" id="mediaDetailSize"></dd>

                                <dt class="col-sm-4">Type:</dt>
                                <dd class="col-sm-8" id="mediaDetailType"></dd>

                                <dt class="col-sm-4">Uploaded:</dt>
                                <dd class="col-sm-8" id="mediaDetailDate"></dd>

                                <dt class="col-sm-4">WP URL:</dt>
                                <dd class="col-sm-8">
                                    <a href="#" id="mediaDetailUrl" target="_blank" class="text-break">View on
                                        WordPress</a>
                                </dd>
                            </dl>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" onclick="copyMediaUrl()">
                                    <i class="bx bx-copy"></i> Copy URL
                                </button>
                                @if (Auth::user()->role == "admin")
                                    <button class="btn btn-sm btn-danger" onclick="deleteMediaFromModal()">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const siteId = {{ $siteId }};
        let allMedia = [];
        let currentView = 'grid';
        let currentMediaId = null;

        // Load media on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadMedia();
        });

        // Load media from API
        function loadMedia() {
            fetch(`/api/wordpress-media/${siteId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allMedia = data.data;
                        renderMedia(allMedia);
                    } else {
                        showAlert('error', data.message || 'Failed to load media');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to load media: ' + error.message);
                });
        }

        // Render media in grid or list view
        function renderMedia(media) {
            const container = document.getElementById('mediaContainer');

            if (media.length === 0) {
                container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bx bx-image" style="font-size: 48px; color: #ccc;"></i>
                    <p class="text-muted">No media files found. Upload some to get started!</p>
                </div>
            `;
                return;
            }

            if (currentView === 'grid') {
                renderGridView(media);
            } else {
                renderListView(media);
            }
        }

        // Render grid view
        function renderGridView(media) {
            const container = document.getElementById('mediaContainer');
            let html = '';

            media.forEach(item => {
                const isImage = item.mime_type && item.mime_type.startsWith('image/');
                const thumbnail = isImage ? item.wp_url : '/public/assetsNew/img/file-icon.png';

                html += `
                <div class="col-md-3 col-sm-4 col-6">
                    <div class="card media-card h-100" onclick="showMediaDetail(${item.id})">
                        <div class="card-img-top" style="height: 200px; overflow: hidden; cursor: pointer;">
                            <img src="${thumbnail}" class="img-fluid" alt="${item.original_filename}"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="card-body p-2">
                            <p class="card-text small mb-1 text-truncate" title="${item.original_filename}">
                                ${item.original_filename}
                            </p>
                            <small class="text-muted">${formatFileSize(item.file_size)}</small>
                        </div>
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        // Render list view
        function renderListView(media) {
            const container = document.getElementById('mediaContainer');
            let html =
                '<div class="col-12"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Preview</th><th>Filename</th><th>Size</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>';

            media.forEach(item => {
                const isImage = item.mime_type && item.mime_type.startsWith('image/');
                const thumbnail = isImage ? item.wp_url : '/public/assetsNew/img/file-icon.png';
                const uploadDate = new Date(item.created_at).toLocaleDateString();

                html += `
                <tr>
                    <td><img src="${thumbnail}" alt="${item.original_filename}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                    <td>${item.original_filename}</td>
                    <td>${formatFileSize(item.file_size)}</td>
                    <td><span class="badge bg-info">${item.mime_type || 'Unknown'}</span></td>
                    <td>${uploadDate}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="showMediaDetail(${item.id}); event.stopPropagation();">
                            <i class="bx bx-show"></i>
                        </button>
                    </td>
                </tr>
            `;
            });

            html += '</tbody></table></div></div>';
            container.innerHTML = html;
        }

        // Show media detail modal
        function showMediaDetail(mediaId) {
            const media = allMedia.find(m => m.id === mediaId);
            if (!media) return;

            currentMediaId = mediaId;

            document.getElementById('mediaDetailTitle').textContent = media.original_filename;
            document.getElementById('mediaDetailImage').src = media.wp_url;
            document.getElementById('mediaDetailFilename').textContent = media.original_filename;
            document.getElementById('mediaDetailSize').textContent = formatFileSize(media.file_size);
            document.getElementById('mediaDetailType').textContent = media.mime_type || 'Unknown';
            document.getElementById('mediaDetailDate').textContent = new Date(media.created_at).toLocaleString();
            document.getElementById('mediaDetailUrl').href = media.wp_url;
            document.getElementById('mediaDetailUrl').textContent = media.wp_url;

            const modal = new bootstrap.Modal(document.getElementById('mediaDetailModal'));
            modal.show();
        }

        // Upload media
        function uploadMedia() {
            const input = document.getElementById('mediaFile');
            const files = input.files;

            if (files.length === 0) {
                showAlert('error', 'Please select at least one file');
                return;
            }

            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('uploadProgressBar');
            const statusText = document.getElementById('uploadStatus');

            progressDiv.style.display = 'block';
            let uploadedCount = 0;

            Array.from(files).forEach((file, index) => {
                const formData = new FormData();
                formData.append('wp_site_id', siteId);
                formData.append('file', file);

                fetch('/api/wordpress-media', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        uploadedCount++;
                        const progress = (uploadedCount / files.length) * 100;
                        progressBar.style.width = progress + '%';
                        statusText.textContent = `Uploaded ${uploadedCount} of ${files.length} files...`;

                        if (uploadedCount === files.length) {
                            progressDiv.style.display = 'none';
                            showAlert('success', `Successfully uploaded ${uploadedCount} file(s)`);
                            bootstrap.Modal.getInstance(document.getElementById('uploadMediaModal')).hide();
                            input.value = '';
                            loadMedia();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', `Failed to upload ${file.name}: ${error.message}`);
                    });
            });
        }

        // Delete media from modal
        function deleteMediaFromModal() {
            if (!currentMediaId) return;

            if (!confirm('Are you sure you want to delete this media file?')) return;

            // Note: Add delete functionality if needed
            showAlert('info', 'Delete functionality not implemented yet');
        }

        // Copy media URL
        function copyMediaUrl() {
            const url = document.getElementById('mediaDetailUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                showAlert('success', 'URL copied to clipboard!');
            }).catch(err => {
                showAlert('error', 'Failed to copy URL');
            });
        }

        // Filter media
        function filterMedia() {
            const typeFilter = document.getElementById('mediaTypeFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            let filtered = allMedia;

            if (typeFilter) {
                filtered = filtered.filter(media => {
                    if (typeFilter === 'image') {
                        return media.mime_type && media.mime_type.startsWith('image/');
                    } else if (typeFilter === 'document') {
                        return media.mime_type && !media.mime_type.startsWith('image/');
                    }
                    return true;
                });
            }

            if (searchTerm) {
                filtered = filtered.filter(media =>
                    media.original_filename.toLowerCase().includes(searchTerm)
                );
            }

            renderMedia(filtered);
        }

        // Handle search with debounce
        let searchTimeout;

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterMedia();
            }, 500);
        }

        // Set view type
        function setView(view) {
            currentView = view;
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('button').classList.add('active');
            renderMedia(allMedia);
        }

        // Format file size
        function formatFileSize(bytes) {
            if (!bytes) return 'Unknown';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // Show alert message
        function showAlert(type, message) {
            const alertDiv = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' :
                type === 'info' ? 'alert-info' : 'alert-danger';
            const icon = type === 'success' ? 'bx-check-circle' :
                type === 'info' ? 'bx-info-circle' : 'bx-error-circle';

            alertDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class='bx ${icon} me-2'></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }
    </script>

    <style>
        .media-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .media-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card-img-top img {
            transition: transform 0.3s;
        }

        .media-card:hover .card-img-top img {
            transform: scale(1.05);
        }
    </style>
@endsection
