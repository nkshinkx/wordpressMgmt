@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<style>
    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        height: 100%;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    .section-title {
        position: relative;
        display: inline-block;
        padding-bottom: 10px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: #667eea;
    }
    .tech-badge {
        display: inline-block;
        padding: 6px 14px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        margin: 4px;
        font-size: 0.875rem;
    }
    .info-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 2.5rem;
    }
</style>

<div class="row mb-4">
    <div class="col-12 text-center">
        <h1 class="display-4 fw-bold mb-3">WordPress Management System</h1>
        <p class="lead text-muted mb-4">
            A Laravel-based centralized management system for managing multiple WordPress sites through the WordPress REST API.
        </p>
        @guest
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4 py-2">Login</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg px-4 py-2 me-2">Go to Dashboard</a>
            <a href="{{ route('wordpress.sites.list') }}" class="btn btn-outline-primary btn-lg px-4 py-2">Manage Sites</a>
        @endguest
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="info-section">
            <h3 class="fw-bold mb-3">Overview</h3>
            <p class="text-muted mb-0">
                This project was created after discovering the powerful WordPress REST API capabilities. By leveraging the JWT Authentication plugin, 
                this system provides a unified interface to manage content across multiple WordPress sites without needing to log into each one individually.
            </p>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12 text-center mb-4">
        <h2 class="section-title fw-bold">Features</h2>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">Site Management</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Connect and manage multiple WordPress sites from one dashboard</li>
                    <li class="mb-2">• Real-time connection status monitoring</li>
                    <li class="mb-2">• JWT token auto-refresh mechanism</li>
                    <li class="mb-2">• Test connection functionality to verify site accessibility</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">Post Management</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Create and edit posts locally before pushing to WordPress</li>
                    <li class="mb-2">• Push posts to WordPress as drafts or publish directly</li>
                    <li class="mb-2">• Schedule posts for future publishing</li>
                    <li class="mb-2">• Sync existing posts from WordPress sites</li>
                    <li class="mb-2">• Track post history and changes</li>
                    <li class="mb-2">• Support for categories, tags, and featured images</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">Media Management</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Upload media files to WordPress sites</li>
                    <li class="mb-2">• Sync existing media from WordPress</li>
                    <li class="mb-2">• Media library browsing per site</li>
                    <li class="mb-2">• Support for images, PDFs, and documents</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">User Management</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Role-based access control (Admin, Manager, User)</li>
                    <li class="mb-2">• Admin: Full system access including user and site management</li>
                    <li class="mb-2">• Manager: Can manage sites and posts</li>
                    <li class="mb-2">• User: Can create and manage posts only</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">Category & Tag Management</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Sync categories and tags from WordPress sites</li>
                    <li class="mb-2">• Create new categories and tags</li>
                    <li class="mb-2">• Automatic synchronization support</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card feature-card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3">Sync & History</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2">• Complete data synchronization (posts, media, categories, tags)</li>
                    <li class="mb-2">• Post history tracking with user attribution</li>
                    <li class="mb-2">• Last sync timestamp tracking</li>
                    <li class="mb-2">• Error logging and reporting</li>
                </ul>
            </div>
        </div>
    </div>
</div>


<div class="row mb-5">
    <div class="col-12">
        <div class="card bg-light border-0">
            <div class="card-body p-5 text-center">
                <h3 class="fw-bold mb-3">Ready to streamline your WordPress management?</h3>
                <p class="text-muted mb-4">Experience efficient multi-site WordPress management from a single unified platform</p>
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

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 bg-light">
            <div class="card-body p-4">
                <p class="text-muted mb-0">
                    <strong>Note:</strong> This is a learning project created to explore WordPress REST API capabilities. 
                    Use in production environments at your own discretion and ensure proper security measures are in place.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

