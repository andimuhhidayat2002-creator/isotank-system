@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-decoration-none me-3"><i class="bi bi-arrow-left"></i> Back</a>
        <h2 class="fw-bold m-0">Inspection Performance</h2>
    </div>
    <div class="alert alert-info shadow-sm">
        <i class="bi bi-info-circle-fill me-2"></i> This module is currently under development.
    </div>
</div>
@endsection
