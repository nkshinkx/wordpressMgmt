@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<style>
    .hero-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .feature-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .feature-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .stat-badge {
        display: inline-block;
        padding: 8px 16px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        margin: 5px;
    }
</style>

<div class="row mb-5">
    <div class="col-12">
        <div class="hero-gradient text-center p-5">
            <div class="py-4">
                <h1 class="display-3 fw-bold mb-3">WordPress Management System</h1>
                <p class="lead mb-4 fs-4">
                    Centralized platform to manage multiple WordPress sites, create content, and collaborate with your team
                </p>
                
                @auth
                    <div class="mb-4">
                        <div class="stat-badge">
                            <strong>{{ \App\Models\WpSites::count() }}</strong> Sites
                        </div>
                        <div class="stat-badge">
                            <strong>{{ \App\Models\WpPost::count() }}</strong> Posts
                        </div>
                        <div class="stat-badge">
                            <strong>{{ \App\Models\WpMedia::count() }}</strong> Media Files
                        </div>
                    </div>
                @endauth
                
                <div class="mt-4">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg px-5 py-3 me-3">
                            <strong>🔑 Login</strong>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg px-5 py-3 me-3">
                            <strong>📊 Go to Dashboard</strong>
                        </a>
                        <a href="{{ route('wordpress.sites.list') }}" class="btn btn-outline-light btn-lg px-5 py-3">
                            <strong>🌐 Manage Sites</strong>
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12 text-center mb-4">
        <h2 class="fw-bold">✨ Key Features</h2>
        <p class="text-muted">Everything you need to manage your WordPress sites effectively</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">🌐</div>
                <h5 class="card-title fw-bold">Multi-Site Management</h5>
                <p class="card-text text-muted">Connect and manage multiple WordPress sites from a single dashboard. Monitor status and synchronize content effortlessly.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">✍️</div>
                <h5 class="card-title fw-bold">Content Creation</h5>
                <p class="card-text text-muted">Create and edit posts with a user-friendly editor. Schedule publications and manage drafts all in one place.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">🖼️</div>
                <h5 class="card-title fw-bold">Media Management</h5>
                <p class="card-text text-muted">Upload and organize media files. Access your entire media library and use images across multiple sites.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">🔐</div>
                <h5 class="card-title fw-bold">Secure Authentication</h5>
                <p class="card-text text-muted">Enterprise-grade security with password hashing, JWT tokens, and secure session management.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">👥</div>
                <h5 class="card-title fw-bold">Role-Based Access</h5>
                <p class="card-text text-muted">Three-tier role system (Admin, Manager, User) ensures proper access control and team collaboration.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="card feature-card h-100 text-center p-4">
            <div class="card-body">
                <div class="feature-icon">🔄</div>
                <h5 class="card-title fw-bold">Auto Synchronization</h5>
                <p class="card-text text-muted">Keep your content synchronized across all your WordPress sites with automatic token refresh.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="card bg-light border-0">
            <div class="card-body p-5 text-center">
                <h3 class="fw-bold mb-3">🚀 Ready to streamline your WordPress management?</h3>
                <p class="text-muted mb-4 fs-5">Join our platform and experience efficient multi-site WordPress management</p>
                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-5 py-3">
                        <strong>Get Started Now</strong>
                    </a>
                @else
                    <a href="{{ route('wordpress.sites.list') }}" class="btn btn-primary btn-lg px-5 py-3">
                        <strong>Add Your First Site</strong>
                    </a>
                @endguest
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 text-center">
        <p class="text-muted">
            <small>Powered by Laravel &amp; Bootstrap • Built for WordPress Professionals</small>
        </p>
    </div>
</div>
@endsection
