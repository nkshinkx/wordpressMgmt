@extends('layouts.app')

@section('title', '403 - Access Denied')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card text-center">
            <div class="card-body py-5">
                <h1 class="display-1 text-danger">403</h1>
                <h2 class="mb-3">Access Denied</h2>
                <p class="lead text-muted mb-4">
                    {{ $exception->getMessage() ?: 'You do not have permission to access this page.' }}
                </p>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection


