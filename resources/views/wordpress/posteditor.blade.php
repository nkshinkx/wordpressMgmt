@extends('layouts.app')

@section('title', 'Wordpress Post Editor')

@section('content')


    @if (!isset($csrfTokenSet))
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/40.0.0/ckeditor5.css" />

    <style>
        .media-item .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .media-item .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .media-item .card.border-primary {
            border-width: 3px !important;
        }

        #mediaLibraryGrid {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>

    <div class="page-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 pb-2">
                            <span id="pageTitle">{{ $post ? 'Edit Post' : 'Create New Post' }}</span>
                            <span class="badge bg-info ms-2" id="statusBadge"></span>
                        </h4>
                        <div>
                            <a href="{{ url('wordpress-posts/' . $siteId) }}" class="btn btn-secondary btn-md">
                                <i class="bx bx-arrow-back"></i> Back to Posts
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Post Editor Form -->
            <form id="postForm">
                <div class="row">
                    <!-- Main Content Area -->
                    <div class="col-lg-8">
                        <!-- Title Card -->
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="postTitle" class="form-label">Post Title <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="postTitle"
                                        placeholder="Enter post title..." required>
                                </div>
                            </div>
                        </div>

                        <!-- Content Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Post Content</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="postContent" class="form-label">Content <span
                                            class="text-danger">*</span></label>
                                    <div id="editor"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Excerpt Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Post Excerpt</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="postExcerpt" class="form-label">Excerpt (Optional)</label>
                                    <textarea class="form-control" id="postExcerpt" rows="3" placeholder="Brief description of the post..."></textarea>
                                    <small class="text-muted">Leave empty to auto-generate from content</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Publish Card -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title text-white mb-0">Publish</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div id="currentStatus" class="alert alert-info mb-2">
                                        <strong>Local Draft</strong> - Not on WordPress
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" id="saveLocalBtn">
                                        <i class="bx bx-save"></i> Save Draft Locally
                                    </button>
                                    <button type="button" class="btn btn-success" id="pushWordPressBtn" disabled>
                                        <i class="bx bx-cloud-upload"></i> Push to WordPress
                                    </button>
                                    <button type="button" class="btn btn-warning" id="previewBtn" style="display: none;">
                                        <i class="bx bx-show"></i> Preview
                                    </button>
                                    <button type="button" class="btn btn-info" id="publishBtn" style="display: none;">
                                        <i class="bx bx-check-circle"></i> Publish Now
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Featured Image Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Featured Image</h5>
                            </div>
                            <div class="card-body">
                                <div id="featuredImagePreview" class="text-center mb-3" style="display: none;">
                                    <img id="featuredImageImg" src="" alt="Featured Image" class="img-fluid rounded"
                                        style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-2"
                                        onclick="removeFeaturedImage()">
                                        <i class="bx bx-trash"></i> Remove
                                    </button>
                                </div>
                                <div id="featuredImageSelect">
                                    <button type="button" class="btn btn-primary w-100" onclick="openMediaLibrary()">
                                        <i class="bx bx-image-add"></i> Select from Media Library
                                    </button>
                                    <small class="text-muted d-block mt-2">Choose an image from your uploaded media</small>
                                </div>
                            </div>
                        </div>

                        <!-- Categories Card -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Categories</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="syncCategories()">
                                    <i class="bx bx-refresh"></i> Sync
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="categoriesList">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tags Card -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Tags</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="syncTags()">
                                    <i class="bx bx-refresh"></i> Sync
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" id="tagSearch"
                                        placeholder="Search tags...">
                                </div>
                                <div id="tagsList" style="max-height: 200px; overflow-y: auto;">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Media Library Modal -->
    <div class="modal fade" id="mediaLibraryModal" tabindex="-1" aria-labelledby="mediaLibraryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaLibraryModalLabel">Select Featured Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="mediaSearch" placeholder="Search media...">
                    </div>
                    <div id="mediaLibraryGrid" class="row g-3">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ url('wordpress-media/' . $siteId) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="bx bx-upload"></i> Upload New Media
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- CKEditor Script -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

    <script>
        const siteId = {{ $siteId }};
        const postId = {{ $post->id ?? 'null' }};
        let editor;
        let currentPost = null;
        let featuredImageId = null;

        // Initialize CKEditor
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', 'undo', 'redo'
                ],
                heading: {
                    options: [{
                            model: 'paragraph',
                            title: 'Paragraph',
                            class: 'ck-heading_paragraph'
                        },
                        {
                            model: 'heading1',
                            view: 'h1',
                            title: 'Heading 1',
                            class: 'ck-heading_heading1'
                        },
                        {
                            model: 'heading2',
                            view: 'h2',
                            title: 'Heading 2',
                            class: 'ck-heading_heading2'
                        },
                        {
                            model: 'heading3',
                            view: 'h3',
                            title: 'Heading 3',
                            class: 'ck-heading_heading3'
                        }
                    ]
                }
            })
            .then(newEditor => {
                editor = newEditor;
                if (postId) {
                    loadPost();
                }
            })
            .catch(error => {
                console.error(error);
                showAlert('error', 'Failed to initialize editor');
            });

        // Load existing post if editing
        function loadPost() {
            fetch(`/api/wordpress-post/${postId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentPost = data.data;
                        populateForm(currentPost);
                    } else {
                        showAlert('error', 'Failed to load post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to load post: ' + error.message);
                });
        }

        // Populate form with post data
        function populateForm(post) {
            document.getElementById('postTitle').value = post.title;
            editor.setData(post.content);
            document.getElementById('postExcerpt').value = post.excerpt || '';

            if (post.featured_image) {
                featuredImageId = post.featured_image_id;
                showFeaturedImage(post.featured_image.wp_url);
            }

            updateStatus(post.status);
            updateButtons(post.status);

            // Load and select categories
            if (post.categories && post.categories.length > 0) {
                setTimeout(() => selectCategories(post.categories), 1000);
            }

            // Load and select tags
            if (post.tags && post.tags.length > 0) {
                setTimeout(() => selectTags(post.tags), 1000);
            }
        }

        // Save draft locally
        document.getElementById('saveLocalBtn').addEventListener('click', function() {
            const title = document.getElementById('postTitle').value.trim();
            const content = editor.getData();

            if (!title || !content) {
                showAlert('error', 'Title and content are required');
                return;
            }

            const postData = {
                wp_site_id: siteId,
                title: title,
                content: content,
                excerpt: document.getElementById('postExcerpt').value.trim(),
                featured_image_id: featuredImageId,
                categories: getSelectedCategories(),
                tags: getSelectedTags()
            };

            const url = postId ? `/api/wordpress-posts/${postId}` : '/api/wordpress-posts';
            const method = postId ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(postData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        currentPost = data.data;

                        // Update page if creating new post
                        if (!postId) {
                            window.history.pushState({}, '', `/wordpress-post-editor?post_id=${data.data.id}`);
                            location.reload();
                        } else {
                            updateStatus(data.data.status);
                            updateButtons(data.data.status);
                        }
                    } else {
                        showAlert('error', data.message || 'Failed to save post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to save post: ' + error.message);
                });
        });

        // Push to WordPress
        document.getElementById('pushWordPressBtn').addEventListener('click', function() {
            if (!postId) {
                showAlert('error', 'Please save the post locally first');
                return;
            }

            if (!confirm('Push this post to WordPress as a draft?')) return;

            fetch(`/api/wordpress-posts/${postId}/push`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        currentPost = data.data;
                        updateStatus(data.data.status);
                        updateButtons(data.data.status);
                    } else {
                        showAlert('error', data.message || 'Failed to push to WordPress');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to push: ' + error.message);
                });
        });

        // Publish post
        document.getElementById('publishBtn').addEventListener('click', function() {
            if (!confirm('Are you sure you want to publish this post to WordPress?')) return;

            fetch(`/api/wordpress-posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        currentPost = data.data;
                        updateStatus(data.data.status);
                        updateButtons(data.data.status);
                        if (data.live_url) {
                            setTimeout(() => window.open(data.live_url, '_blank'), 1000);
                        }
                    } else {
                        showAlert('error', data.message || 'Failed to publish');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to publish: ' + error.message);
                });
        });

        // Preview post
        document.getElementById('previewBtn').addEventListener('click', function() {
            if (currentPost && currentPost.wp_post_id) {
                const previewUrl = `${currentPost.wp_site.domain}/?p=${currentPost.wp_post_id}&preview=true`;
                window.open(previewUrl, '_blank');
            } else {
                showAlert('error', 'Post not yet pushed to WordPress');
            }
        });

        // Update status display
        function updateStatus(status) {
            const statusMap = {
                'local_draft': {
                    text: 'Local Draft',
                    class: 'alert-secondary',
                    desc: 'Saved locally, not on WordPress'
                },
                'pushed_draft': {
                    text: 'Pushed Draft',
                    class: 'alert-info',
                    desc: 'Draft saved on WordPress'
                },
                'published': {
                    text: 'Published',
                    class: 'alert-success',
                    desc: 'Live on WordPress'
                },
                'out_of_sync': {
                    text: 'Out of Sync',
                    class: 'alert-warning',
                    desc: 'Local changes not synced'
                },
                'failed': {
                    text: 'Failed',
                    class: 'alert-danger',
                    desc: 'Last operation failed'
                }
            };

            const statusInfo = statusMap[status] || statusMap['local_draft'];
            const statusDiv = document.getElementById('currentStatus');
            statusDiv.className = `alert ${statusInfo.class} mb-2`;
            statusDiv.innerHTML = `<strong>${statusInfo.text}</strong> - ${statusInfo.desc}`;

            document.getElementById('statusBadge').textContent = statusInfo.text;
            document.getElementById('statusBadge').className = `badge ms-2 bg-${statusInfo.class.replace('alert-', '')}`;
        }

        // Update button visibility based on status
        function updateButtons(status) {
            const pushBtn = document.getElementById('pushWordPressBtn');
            const previewBtn = document.getElementById('previewBtn');
            const publishBtn = document.getElementById('publishBtn');

            pushBtn.disabled = false;
            previewBtn.style.display = 'none';
            publishBtn.style.display = 'none';

            if (status === 'pushed_draft' || status === 'published') {
                previewBtn.style.display = 'block';
            }

            if (status === 'pushed_draft' || status === 'out_of_sync') {
                publishBtn.style.display = 'block';
            }
        }

        // Open media library modal
        function openMediaLibrary() {
            const modal = new bootstrap.Modal(document.getElementById('mediaLibraryModal'));
            modal.show();
            loadMediaLibrary();
        }

        // Load media library
        function loadMediaLibrary() {
            const grid = document.getElementById('mediaLibraryGrid');
            grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

            fetch(`/api/wordpress-media/${siteId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMediaLibrary(data.data);
                    } else {
                        grid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle"></i> No media found. 
                            <a href="/wordpress-media/${siteId}" target="_blank">Upload some media first</a>
                        </div>
                    </div>
                `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Failed to load media library</div>
                </div>
            `;
                });
        }

        // Render media library grid
        function renderMediaLibrary(mediaItems) {
            const grid = document.getElementById('mediaLibraryGrid');

            if (mediaItems.length === 0) {
                grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle"></i> No media uploaded yet. 
                        <a href="/wordpress-media/${siteId}" target="_blank">Upload media now</a>
                    </div>
                </div>
            `;
                return;
            }

            let html = '';
            mediaItems.forEach(media => {
                const isSelected = featuredImageId === media.id;
                html += `
                <div class="col-md-3 col-sm-4 col-6 media-item" data-media-id="${media.id}" data-filename="${media.original_filename.toLowerCase()}">
                    <div class="card h-100 ${isSelected ? 'border-primary' : ''}" style="cursor: pointer;" onclick="selectFeaturedImage(${media.id}, '${media.wp_url}')">
                        <div class="position-relative">
                            <img src="${media.wp_url}" class="card-img-top" alt="${media.original_filename}" 
                                 style="height: 150px; object-fit: cover;">
                            ${isSelected ? '<div class="position-absolute top-0 end-0 m-2"><i class="bx bx-check-circle text-primary fs-3"></i></div>' : ''}
                        </div>
                        <div class="card-body p-2">
                            <p class="card-text small mb-0 text-truncate" title="${media.original_filename}">
                                ${media.original_filename}
                            </p>
                            <small class="text-muted">${formatFileSize(media.file_size)}</small>
                        </div>
                    </div>
                </div>
            `;
            });

            grid.innerHTML = html;

            // Add search functionality
            document.getElementById('mediaSearch').addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                document.querySelectorAll('.media-item').forEach(item => {
                    const filename = item.getAttribute('data-filename');
                    item.style.display = filename.includes(search) ? 'block' : 'none';
                });
            });
        }

        // Select featured image from library
        function selectFeaturedImage(mediaId, mediaUrl) {
            featuredImageId = mediaId;
            showFeaturedImage(mediaUrl);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('mediaLibraryModal'));
            modal.hide();

            showAlert('success', 'Featured image selected');
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Show featured image preview
        function showFeaturedImage(url) {
            document.getElementById('featuredImageImg').src = url;
            document.getElementById('featuredImagePreview').style.display = 'block';
            document.getElementById('featuredImageSelect').style.display = 'none';
        }

        // Remove featured image
        function removeFeaturedImage() {
            featuredImageId = null;
            document.getElementById('featuredImagePreview').style.display = 'none';
            document.getElementById('featuredImageSelect').style.display = 'block';
        }

        // Load categories
        function loadCategories() {
            fetch(`/api/wordpress-categories/${siteId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCategories(data.data);
                    } else {
                        document.getElementById('categoriesList').innerHTML =
                            '<div class="alert alert-warning">Failed to load categories. <a href="#" onclick="syncCategories()">Sync now</a></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('categoriesList').innerHTML =
                        '<div class="alert alert-danger">Error loading categories</div>';
                });
        }

        // Render categories
        function renderCategories(categories) {
            const container = document.getElementById('categoriesList');

            if (categories.length === 0) {
                container.innerHTML =
                    '<div class="alert alert-info">No categories found. <a href="#" onclick="syncCategories()">Sync now</a></div>';
                return;
            }

            let html = '';
            categories.forEach(cat => {
                html += `
                <div class="form-check">
                    <input class="form-check-input category-checkbox" type="checkbox" 
                           value="${cat.wp_category_id}" id="cat${cat.id}">
                    <label class="form-check-label" for="cat${cat.id}">
                        ${cat.name}
                    </label>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        // Sync categories
        function syncCategories() {
            showAlert('info', 'Syncing categories...');

            fetch(`/api/wordpress-categories/${siteId}/sync`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', `Synced ${data.count} categories`);
                        loadCategories();
                    } else {
                        showAlert('error', data.message || 'Failed to sync categories');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to sync categories: ' + error.message);
                });
        }

        // Load tags
        function loadTags() {
            fetch(`/api/wordpress-tags/${siteId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderTags(data.data);
                    } else {
                        document.getElementById('tagsList').innerHTML =
                            '<div class="alert alert-warning">Failed to load tags. <a href="#" onclick="syncTags()">Sync now</a></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('tagsList').innerHTML =
                        '<div class="alert alert-danger">Error loading tags</div>';
                });
        }

        // Render tags
        function renderTags(tags) {
            const container = document.getElementById('tagsList');

            if (tags.length === 0) {
                container.innerHTML =
                    '<div class="alert alert-info">No tags found. <a href="#" onclick="syncTags()">Sync now</a></div>';
                return;
            }

            let html = '';
            tags.forEach(tag => {
                html += `
                <div class="form-check">
                    <input class="form-check-input tag-checkbox" type="checkbox" 
                           value="${tag.wp_tag_id}" id="tag${tag.id}">
                    <label class="form-check-label" for="tag${tag.id}">
                        ${tag.name}
                    </label>
                </div>
            `;
            });

            container.innerHTML = html;

            // Add search functionality
            document.getElementById('tagSearch').addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                document.querySelectorAll('#tagsList .form-check').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(search) ? 'block' : 'none';
                });
            });
        }

        // Sync tags
        function syncTags() {
            showAlert('info', 'Syncing tags...');

            fetch(`/api/wordpress-tags/${siteId}/sync`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', `Synced ${data.count} tags`);
                        loadTags();
                    } else {
                        showAlert('error', data.message || 'Failed to sync tags');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to sync tags: ' + error.message);
                });
        }

        // Get selected categories
        function getSelectedCategories() {
            const checkboxes = document.querySelectorAll('.category-checkbox:checked');
            return Array.from(checkboxes).map(cb => parseInt(cb.value));
        }

        // Get selected tags
        function getSelectedTags() {
            const checkboxes = document.querySelectorAll('.tag-checkbox:checked');
            return Array.from(checkboxes).map(cb => parseInt(cb.value));
        }

        // Select categories (when loading existing post)
        function selectCategories(categoryIds) {
            categoryIds.forEach(id => {
                const checkbox = document.querySelector(`.category-checkbox[value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        // Select tags (when loading existing post)
        function selectTags(tagIds) {
            tagIds.forEach(id => {
                const checkbox = document.querySelector(`.tag-checkbox[value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
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

            if (type !== 'info') {
                setTimeout(() => {
                    alertDiv.innerHTML = '';
                }, 5000);
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadTags();
        });
    </script>

@endsection
