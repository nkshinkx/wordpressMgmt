@extends('layouts.app')

@section('title', 'Manage WordPress Posts')

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
                        <h4 class="mb-sm-0 pb-2">Manage WordPress Posts - {{ $wpSite->site_name ?? 'All Sites' }}</h4>
                        <div>
                            <a href="{{ route('wordpress.sites.list') }}" class="btn btn-secondary btn-md me-2">
                                <i class="bx bx-arrow-back"></i> Back to Sites
                            </a>
                            <button class="btn btn-success btn-md" onclick="createNewPost()">
                                <i class="bx bx-plus-circle"></i> New Post
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Filter & Search -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Status Filter</label>
                                    <select class="form-select" id="statusFilter" onchange="filterPosts()">
                                        <option value="">All Statuses</option>
                                        <option value="local_draft">Local Draft</option>
                                        <option value="pushed_draft">Pushed Draft</option>
                                        <option value="published">Published</option>
                                        <option value="out_of_sync">Out of Sync</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Search</label>
                                    <input type="text" class="form-control" id="searchInput"
                                        placeholder="Search by title or content..." onkeyup="handleSearch()">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-primary w-100" onclick="filterPosts()">
                                        <i class="bx bx-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Posts List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="postsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>WP Status</th>
                                            <th>Last Updated</th>
                                            <th>Last Synced</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="postsTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="pagination" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const siteId = {{ $siteId ?? 'null' }};
        let currentPage = 1;
        const userAccess = {{ Auth::user()->role }};
        // Load posts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPosts();
        });

        // Load posts from API
        function loadPosts(page = 1) {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;

            let url = `/api/wordpress-posts/${siteId}?page=${page}`;
            if (status) url += `&status=${status}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;

            fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderPosts(data.data);
                    } else {
                        showAlert('error', data.message || 'Failed to load posts');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to load posts: ' + error.message);
                });
        }

        // Render posts table
        function renderPosts(postsData) {
            const tbody = document.getElementById('postsTableBody');

            if (postsData.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No posts found</td></tr>';
                return;
            }

            let html = '';
            postsData.data.forEach(post => {
                const statusBadge = getStatusBadge(post.status);
                const wpStatusBadge = getWpStatusBadge(post.wp_status);
                const lastUpdated = new Date(post.updated_at).toLocaleString();
                const lastSynced = post.last_synced_at ? new Date(post.last_synced_at).toLocaleString() : 'Never';

                html += `
                <tr>
                    <td>${post.id}</td>
                    <td>
                        <strong>${post.title.substring(0, 50)}${post.title.length > 50 ? '...' : ''}</strong>
                    </td>
                    <td>${statusBadge}</td>
                    <td>${wpStatusBadge}</td>
                    <td>${lastUpdated}</td>
                    <td>${lastSynced}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info" onclick="editPost(${post.id})" title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            ${post.status === 'local_draft' || post.status === 'out_of_sync' ? `
                                    <button class="btn btn-primary" onclick="pushPost(${post.id})" title="Push to WordPress">
                                        <i class="bx bx-cloud-upload"></i>
                                    </button>
                                ` : ''}
                            ${post.status === 'pushed_draft' ? `
                                    <button class="btn btn-success" onclick="publishPost(${post.id})" title="Publish">
                                        <i class="bx bx-check-circle"></i>
                                    </button>
                                ` : ''}
                            ${post.wp_post_id ? `
                                    <button class="btn btn-secondary" onclick="previewPost(${post.id})" title="Preview">
                                        <i class="bx bx-show"></i>
                                    </button>
                                ` : ''}
                            ${userAccess == "admin" ? `
                                    <button class="btn btn-danger" onclick="deletePost(${post.id})" title="Delete">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                ` : ''}
                        </div>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
            renderPagination(postsData);
        }

        // Get status badge HTML
        function getStatusBadge(status) {
            const badges = {
                'local_draft': '<span class="badge bg-secondary">Local Draft</span>',
                'pushed_draft': '<span class="badge bg-info">Pushed Draft</span>',
                'published': '<span class="badge bg-success">Published</span>',
                'out_of_sync': '<span class="badge bg-warning">Out of Sync</span>',
                'failed': '<span class="badge bg-danger">Failed</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }

        // Get WP status badge HTML
        function getWpStatusBadge(wpStatus) {
            const badges = {
                'draft': '<span class="badge bg-secondary">Draft</span>',
                'publish': '<span class="badge bg-success">Published</span>',
                'pending': '<span class="badge bg-warning">Pending</span>',
                'private': '<span class="badge bg-info">Private</span>'
            };
            return badges[wpStatus] || '<span class="badge bg-secondary">Draft</span>';
        }

        // Render pagination
        function renderPagination(postsData) {
            const paginationDiv = document.getElementById('pagination');
            if (postsData.last_page <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }

            let html = '<nav><ul class="pagination justify-content-center">';

            // Previous
            html += `<li class="page-item ${postsData.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadPosts(${postsData.current_page - 1}); return false;">Previous</a>
        </li>`;

            // Pages
            for (let i = 1; i <= postsData.last_page; i++) {
                if (i === 1 || i === postsData.last_page || (i >= postsData.current_page - 2 && i <= postsData
                        .current_page + 2)) {
                    html += `<li class="page-item ${i === postsData.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadPosts(${i}); return false;">${i}</a>
                </li>`;
                } else if (i === postsData.current_page - 3 || i === postsData.current_page + 3) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next
            html += `<li class="page-item ${postsData.current_page === postsData.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadPosts(${postsData.current_page + 1}); return false;">Next</a>
        </li>`;

            html += '</ul></nav>';
            paginationDiv.innerHTML = html;
        }

        // Filter posts
        function filterPosts() {
            loadPosts(1);
        }

        // Handle search with debounce
        let searchTimeout;

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterPosts();
            }, 500);
        }

        // Create new post
        function createNewPost() {
            window.location.href = `/wordpress-post-editor?site_id=${siteId}`;
        }

        // Edit post
        function editPost(postId) {
            window.location.href = `/wordpress-post-editor?post_id=${postId}`;
        }

        // Push post to WordPress
        function pushPost(postId) {
            if (!confirm('Push this post to WordPress as a draft?')) return;

            fetch(`/api/wordpress-posts/${postId}/push`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        loadPosts(currentPage);
                    } else {
                        showAlert('error', data.message || 'Failed to push post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to push post: ' + error.message);
                });
        }

        // Publish post
        function publishPost(postId) {
            if (!confirm('Are you sure you want to publish this post to WordPress?')) return;

            fetch(`/api/wordpress-posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        if (data.live_url) {
                            setTimeout(() => {
                                window.open(data.live_url, '_blank');
                            }, 1000);
                        }
                        loadPosts(currentPage);
                    } else {
                        showAlert('error', data.message || 'Failed to publish post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to publish post: ' + error.message);
                });
        }

        // Preview post
        function previewPost(postId) {
            fetch(`/api/wordpress-post/${postId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.wp_post_id) {
                        const previewUrl = `${data.data.wp_site.domain}/?p=${data.data.wp_post_id}&preview=true`;
                        window.open(previewUrl, '_blank');
                    } else {
                        showAlert('error', 'Post not yet pushed to WordPress');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to get preview link: ' + error.message);
                });
        }

        // Delete post
        function deletePost(postId) {
            if (!confirm(
                    'Are you sure you want to delete this post? This will also delete it from WordPress if it was published.'
                )) return;

            fetch(`/api/wordpress-posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        loadPosts(currentPage);
                    } else {
                        showAlert('error', data.message || 'Failed to delete post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to delete post: ' + error.message);
                });
        }

        // Show alert message
        function showAlert(type, message) {
            const alertDiv = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';

            alertDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class='bx ${icon} me-2'></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }
    </script>
@endsection
