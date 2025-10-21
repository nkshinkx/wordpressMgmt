@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        .stat-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-card.primary {
            border-left-color: #0d6efd;
        }

        .stat-card.success {
            border-left-color: #198754;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.info {
            border-left-color: #0dcaf0;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .bg-primary-soft {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .bg-success-soft {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .bg-info-soft {
            background-color: rgba(13, 202, 240, 0.1);
            color: #0dcaf0;
        }

        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .activity-item {
            padding: 12px;
            border-left: 3px solid #e9ecef;
            margin-bottom: 10px;
            transition: all 0.2s;
        }

        .activity-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }

        .site-card {
            transition: all 0.2s;
        }

        .site-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Welcome back, {{ ucfirst($user->name) }}!</h2>
            <p class="text-muted">Here's what's happening with your WordPress sites today.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card primary h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">WordPress Sites</h6>
                        <h3 class="mb-0">{{ $stats['total_sites'] }}</h3>
                        <small class="text-success">{{ $stats['active_sites'] }} Active</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card success h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Posts</h6>
                        <h3 class="mb-0">{{ $stats['total_posts'] }}</h3>
                        <small class="text-muted">{{ $userStats['user_posts'] }} by you</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card warning h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Draft Posts</h6>
                        <h3 class="mb-0">{{ $stats['draft_posts'] }}</h3>
                        <small class="text-muted">{{ $stats['scheduled_posts'] }} Scheduled</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card stat-card info h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Media Files</h6>
                        <h3 class="mb-0">{{ $stats['total_media'] }}</h3>
                        <small class="text-muted">{{ $userStats['user_media'] }} by you</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & User Info -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('wordpress.sites.list') }}" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-globe fs-4 d-block mb-2"></i>
                                <div>Manage Sites</div>
                            </a>
                        </div>
                        @if ($stats['total_sites'] > 0)
                            <div class="col-md-4">
                                <div class="dropdown w-100">
                                    <button class="btn btn-success w-100 py-3 dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i class="bi bi-pencil-square fs-4 d-block mb-2"></i>
                                        <div>Create Post</div>
                                    </button>
                                    <ul class="dropdown-menu w-100">
                                        @foreach (\App\Models\WpSites::orderBy('site_name')->get() as $site)
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('wordpress.posts.view', $site->id) }}">
                                                    <strong>{{ $site->site_name }}</strong>
                                                    <br><small class="text-muted">{{ $site->domain }}</small>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dropdown w-100">
                                    <button class="btn btn-info w-100 py-3 dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i class="bi bi-folder2-open fs-4 d-block mb-2"></i>
                                        <div>Media Library</div>
                                    </button>
                                    <ul class="dropdown-menu w-100">
                                        @foreach (\App\Models\WpSites::orderBy('site_name')->get() as $site)
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('wordpress.media.view', $site->id) }}">
                                                    <strong>{{ $site->site_name }}</strong>
                                                    <br><small class="text-muted">{{ $site->domain }}</small>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <div class="col-md-4">
                                <button class="btn btn-secondary w-100 py-3" disabled title="Add a WordPress site first">
                                    <i class="bi bi-pencil-square fs-4 d-block mb-2"></i>
                                    <div>Create Post</div>
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-secondary w-100 py-3" disabled title="Add a WordPress site first">
                                    <i class="bi bi-folder2-open fs-4 d-block mb-2"></i>
                                    <div>Media Library</div>
                                </button>
                            </div>
                        @endif
                        @if ($user->isAdmin())
                            <div class="col-md-4">
                                <a href="{{ route('users.index') }}" class="btn btn-warning w-100 py-3">
                                    <i class="bi bi-people-fill fs-4 d-block mb-2"></i>
                                    <div>Manage Users</div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('users.create') }}" class="btn btn-outline-primary w-100 py-3">
                                    <i class="bi bi-person-plus-fill fs-4 d-block mb-2"></i>
                                    <div>Add User</div>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Profile</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 32px;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    </div>
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td class="text-muted">Email:</td>
                                <td class="text-end"><strong>{{ $user->email }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Role:</td>
                                <td class="text-end">
                                    <span
                                        class="badge bg-{{ $user->isAdmin() ? 'danger' : ($user->isManager() ? 'warning' : 'info') }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created At:</td>
                                <td class="text-end"><strong>{{ $user->created_at->format('M d, Y') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- WordPress Sites & Recent Activity -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"> WordPress Sites</h5>
                    <a href="{{ route('wordpress.sites.list') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if ($sites->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($sites as $site)
                                <div class="list-group-item site-card px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $site->site_name }}</h6>
                                            <small class="text-muted">
                                                <a href="{{ $site->domain }}" target="_blank"
                                                    class="text-decoration-none">
                                                    {{ $site->domain }}
                                                </a>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span
                                                class="badge bg-{{ $site->status === 'connected' ? 'success' : 'secondary' }} mb-1">
                                                {{ ucfirst($site->status) }}
                                            </span>
                                            <div>
                                                <small class="text-muted">{{ $site->posts_count }} posts</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-globe display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No WordPress sites yet</h5>
                            <p class="text-muted">Get started by adding your first WordPress site</p>
                            <a href="{{ route('wordpress.sites.list') }}" class="btn btn-primary">
                                Add WordPress Site
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if ($recentActivity->count() > 0)
                        @foreach ($recentActivity as $activity)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $activity['title'] ?? 'Untitled' }}</h6>
                                        <small class="text-muted">
                                            by <strong>{{ $activity['user'] }}</strong> on
                                            <strong>{{ $activity['site'] }}</strong>
                                        </small>
                                    </div>
                                    <span
                                        class="badge bg-{{ $activity['status'] === 'published' ? 'success' : ($activity['status'] === 'draft' ? 'warning' : 'info') }}">
                                        {{ ucfirst($activity['status']) }}
                                    </span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ $activity['updated_at']->diffForHumans() }}
                                </small>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history display-4 text-muted mb-2"></i>
                            <p class="text-muted mb-0">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Posts -->
    @if ($recentPosts->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-file-text text-primary"></i> Recent Posts</h5>
                        @if ($stats['total_sites'] > 0)
                            @php
                                $firstSite = \App\Models\WpSites::first();
                            @endphp
                            @if ($firstSite)
                                <a href="{{ route('wordpress.posts.view', $firstSite->id) }}"
                                    class="btn btn-sm btn-outline-primary">View All Posts</a>
                            @endif
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Site</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentPosts as $post)
                                        <tr>
                                            <td>
                                                <strong>{{ $post->title ?? 'Untitled' }}</strong>
                                                @if ($post->excerpt)
                                                    <br><small
                                                        class="text-muted">{{ Str::limit($post->excerpt, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-secondary">{{ $post->wpSite->site_name ?? 'Unknown' }}</span>
                                            </td>
                                            <td>{{ $post->user->name ?? 'Unknown' }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $post->status === 'published' ? 'success' : ($post->status === 'draft' ? 'warning' : 'info') }}">
                                                    {{ ucfirst($post->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $post->updated_at->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('wordpress.post.editor') }}?post_id={{ $post->id }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Admin Stats -->
    @if ($user->isAdmin())
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header ">
                        <h5 class="mb-0"> Admin Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="text-primary">{{ $stats['total_users'] }}</h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success">{{ $stats['published_posts'] }}</h3>
                                <p class="text-muted mb-0">Published Posts</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-info">{{ $stats['active_sites'] }}</h3>
                                <p class="text-muted mb-0">Active Sites</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning">{{ $stats['draft_posts'] }}</h3>
                                <p class="text-muted mb-0">Pending Drafts</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
