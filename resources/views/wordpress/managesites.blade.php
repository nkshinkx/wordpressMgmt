@extends('layouts.app')

@section('title', 'Manage WordPress Sites')

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
                        <h4 class="mb-sm-0 pb-2">Manage WordPress Sites</h4>
                        @if (Auth::user()->role == 'admin')
                            <button class="btn btn-success btn-md" type="button" data-bs-toggle="modal"
                                data-bs-target="#addWordpressSiteModal">
                                <i class="bx bx-plus-circle"></i> Add WordPress Site
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Search Form -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ url()->current() }}" method="GET" class="row g-3">
                                <div class="col-md-10">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                                        <input type="text" class="form-control" name="search"
                                            placeholder="Search by site name or domain..." value="{{ $search ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bx bx-search me-1"></i> Search
                                    </button>
                                    @if (!empty($search))
                                        <a href="{{ url()->current() }}" class="btn btn-secondary w-100 mt-2">
                                            <i class="bx bx-x me-1"></i> Clear
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WordPress Sites Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <div class="card-title">
                                    WordPress Sites List ({{ $wordpressSites->total() }})
                                    @if (!empty($search))
                                        <span class="badge bg-info ms-2">Search: "{{ $search }}"</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Site Name</th>
                                            <th>Domain</th>
                                            <th>Connection Status</th>
                                            <th>Last Connected</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($wordpressSites as $site)
                                            <tr>
                                                <td>{{ $site->id }}</td>
                                                <td>{{ $site->site_name }}</td>
                                                <td>
                                                    <a href="{{ $site->domain }}" target="_blank" class="text-primary">
                                                        {{ $site->domain }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if ($site->status == 'active')
                                                        <span class="badge bg-success">Active</span>
                                                    @elseif($site->status == 'inactive')
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @else
                                                        <span class="badge bg-warning">Unknown</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $site->last_connected_at ? \Carbon\Carbon::parse($site->last_connected_at)->format('Y-m-d H:i') : 'Never' }}
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ url('wordpress-posts/' . $site->id) }}"
                                                            class="btn btn-sm btn-success" title="Manage Posts">
                                                            <i class="bx bx-file"></i>
                                                        </a>
                                                        <a href="{{ url('wordpress-media/' . $site->id) }}"
                                                            class="btn btn-sm btn-info" title="Media Library">
                                                            <i class="bx bx-image"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                            onclick="syncPosts({{ $site->id }})"
                                                            title="Sync All Data (Categories, Tags, Media, Posts)">
                                                            <i class="bx bx-sync"></i>
                                                        </button>
                                                        @if (Auth::user()->role == 'admin')
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="editSite({{ $site->id }})" title="Edit">
                                                                <i class="bx bx-edit"></i>
                                                            </button>
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                            onclick="testConnection({{ $site->id }})"
                                                            title="Test Connection">
                                                            <i class="bx bx-wifi"></i>
                                                        </button>
                                                        @if (Auth::user()->role == 'admin')
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteSite({{ $site->id }})" title="Delete">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="bx bx-info-circle me-2"></i>
                                                        No WordPress sites found. Click "Add WordPress Site" to get started.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if ($wordpressSites->hasPages())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $wordpressSites->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add WordPress Site Modal -->
    <div class="modal fade" id="addWordpressSiteModal" tabindex="-1" aria-labelledby="addWordpressSiteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWordpressSiteModalLabel">Add WordPress Site</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addWordpressSiteForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_name" class="form-label">Site Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="domain" class="form-label">Domain URL <span
                                            class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="domain" name="domain"
                                        placeholder="https://example.com" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span
                                            class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="auto_refresh"
                                        name="auto_refresh" value="1" checked>
                                    <label class="form-check-label" for="auto_refresh">
                                        Auto-refresh JWT tokens
                                        <small class="text-muted d-block">Automatically refresh authentication tokens every
                                            12 hours</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> Make sure the WordPress site has JWT Authentication plugin installed and
                            configured for API access.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                            Add Site
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit WordPress Site Modal -->
    <div class="modal fade" id="editWordpressSiteModal" tabindex="-1" aria-labelledby="editWordpressSiteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWordpressSiteModalLabel">Edit WordPress Site Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editWordpressSiteForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_site_id" name="site_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_site_name" class="form-label">Site Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_site_name" name="site_name"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_domain" class="form-label">Domain URL <span
                                            class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="edit_domain" name="domain" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_username" name="username"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_password" class="form-label">Password <span
                                            class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="edit_password" name="password"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="edit_auto_refresh"
                                        name="auto_refresh" value="1">
                                    <label class="form-check-label" for="edit_auto_refresh">
                                        Auto-refresh JWT tokens
                                        <small class="text-muted d-block">Automatically refresh authentication tokens every
                                            12 hours</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                            Update Site
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="http://127.0.0.1:8000/assetsNew/js/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Setup CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Add WordPress Site Form
            $('#addWordpressSiteForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const spinner = submitBtn.find('.spinner-border');

                // Reset previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');

                // Show loading state
                submitBtn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route('wordpress.sites.create') }}',
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            $('#addWordpressSiteModal').modal('hide');
                            form[0].reset();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                const input = form.find(`[name="${key}"]`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(errors[key][
                                    0
                                ]);
                            });
                        } else {
                            showAlert('danger', 'An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Edit WordPress Site Form
            $('#editWordpressSiteForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const spinner = submitBtn.find('.spinner-border');
                const siteId = $('#edit_site_id').val();

                // Reset previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');

                // Show loading state
                submitBtn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: `/wordpress-sites/${siteId}`,
                    method: 'PUT',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            $('#editWordpressSiteModal').modal('hide');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                const input = form.find(`[name="${key}"]`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(errors[key][
                                    0
                                ]);
                            });
                        } else {
                            showAlert('danger', 'An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });
        });

        // Edit Site Function
        function editSite(siteId) {

            $('#edit_site_id').val(siteId);
            $.ajax({
                url: `/wordpress-sites/${siteId}`,
                method: 'GET',
                success: function(response) {
                    $('#edit_site_name').val(response.data.site_name);
                    $('#edit_domain').val(response.data.domain);
                    $('#edit_username').val(response.data.username);
                    $('#edit_password').val(response.data.password);
                    $('#edit_auto_refresh').prop('checked', response.data.auto_refresh == 1 || response.data
                        .auto_refresh == true);
                }
            });
            $('#editWordpressSiteModal').modal('show');


        }

        // Delete Site Function
        function deleteSite(siteId) {
            if (confirm('Are you sure you want to delete this WordPress site? This action cannot be undone.')) {
                $.ajax({
                    url: `/wordpress-sites/${siteId}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function() {
                        showAlert('danger', 'An error occurred while deleting the site.');
                    }
                });
            }
        }

        // Test Connection Function
        function testConnection(siteId) {
            showAlert('info', 'Testing connection...');

            $.ajax({
                url: '{{ route('wordpress.connection.test') }}',
                method: 'POST',
                data: {
                    site_id: siteId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Connection test failed';
                    showAlert('danger', errorMessage);
                }
            });
        }

        // Sync All Data Function
        function syncPosts(siteId) {
            if (!confirm(
                    'This will sync ALL data from WordPress (categories, tags, media, posts) to your database. This may take a few moments. Continue?'
                )) {
                return;
            }

            showAlert('info', 'Syncing all data from WordPress... Please wait, this may take a moment.');

            // Disable the button
            const syncBtn = $(`button[onclick="syncPosts(${siteId})"]`);
            syncBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');

            $.ajax({
                url: `/api/wordpress-data/${siteId}/sync`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const results = response.results;
                        let message = response.message;

                        if (results.errors && results.errors.length > 0) {
                            message += '<br><small>Some errors occurred: ' + results.errors.join(', ') +
                                '</small>';
                        }

                        showAlert('success', message);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Failed to sync data';
                    showAlert('danger', errorMessage);
                },
                complete: function() {
                    // Re-enable the button
                    syncBtn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
                }
            });
        }

        // Show Alert Function
        function showAlert(type, message) {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bx bx-${type === 'success' ? 'check-circle' : type === 'danger' ? 'x-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

            $('#alertContainer').html(alertHtml);

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $('.alert').fadeOut();
                }, 3000);
            }
        }
    </script>

    <style>
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .badge {
            font-size: 0.75em;
        }

        .modal-body .alert {
            margin-bottom: 0;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
@endsection
