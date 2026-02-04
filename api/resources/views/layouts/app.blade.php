<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isotank Admin System</title>
    
    <!-- VITE ASSET LOADING (No CDNs) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <nav class="sidebar p-3">
            <!-- 1. Header (Fixed) -->
            <div>
                <div class="text-center mb-4 px-2">
                    <img src="{{ asset('assets/images/logo_isotank_full.jpg') }}" 
                         alt="Isotank Management System" 
                         style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
                
                <!-- Compact Profile -->
                <div class="mb-3 px-2 py-2 rounded" style="background-color: rgba(255, 255, 255, 0.05);">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill fs-6"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-white lh-1 mb-0" style="font-size: 0.9rem;">{{ auth()->user()->name }}</div>
                            <div class="badge bg-white text-primary bg-opacity-100 border-0 p-0 px-1" style="font-size: 0.6rem; font-weight: 700;">
                                {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Scrollable Menu (Flex Grow) -->
            <div class="sidebar-content d-flex flex-column">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.isotanks.index') }}" class="{{ request()->routeIs('admin.isotanks*') ? 'active' : '' }}">Master Isotanks</a>
                
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.activities.index') }}" class="{{ request()->routeIs('admin.activities*') ? 'active' : '' }}">Activity Planner</a>
                    <a href="{{ route('admin.calibration-master.index') }}" class="{{ request()->routeIs('admin.calibration-master*') ? 'active' : '' }}">Calibration Master</a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Users</a>
                    <a href="{{ route('admin.inspection-items.index') }}" class="{{ request()->routeIs('admin.inspection-items*') ? 'active' : '' }}">Inspection Items</a>
                @endif
                
                <a href="{{ route('yard.index') }}" class="{{ request()->routeIs('yard.*') ? 'active' : '' }}">Yard Positioning</a>
                
                <hr style="border-color: rgba(255,255,255,0.1); margin: 6px 0;">
                <small class="text-uppercase text-white-50 px-2 mb-1" style="font-size: 0.7rem; font-weight: bold;">Reports</small>
                
                <a href="{{ route('admin.reports.inspection') }}" class="{{ request()->routeIs('admin.reports.inspection*') ? 'active' : '' }}">Inspection Logs</a>
                <a href="{{ route('admin.reports.latest') }}" class="{{ request()->routeIs('admin.reports.latest') ? 'active' : '' }}">Latest Master Condition</a>
                <a href="{{ route('admin.reports.maintenance') }}" class="{{ request()->routeIs('admin.reports.maintenance*') ? 'active' : '' }}">Maintenance Jobs</a>
                <a href="{{ route('admin.reports.calibration') }}" class="{{ request()->routeIs('admin.reports.calibration') ? 'active' : '' }}">Calibration History</a>
                <a href="{{ route('admin.reports.vacuum') }}" class="{{ request()->routeIs('admin.reports.vacuum') ? 'active' : '' }}">Vacuum Suction</a>

                @if(auth()->user()->role === 'admin')
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 6px 0;">
                    <a href="{{ route('admin.monitoring.index') }}" class="{{ request()->routeIs('admin.monitoring*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check me-2"></i> System Monitoring
                    </a>
                @endif
            </div>
            
            <!-- 3. Footer (Fixed Bottom) -->
            <div class="mt-auto pt-2">
                <hr style="border-color: rgba(255,255,255,0.1); margin: 0 0 10px 0;">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light w-100 py-1 d-flex align-items-center justify-content-center">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <!-- Added margin-left: 260px to compensate for fixed sidebar -->
        <main class="flex-grow-1 p-4" style="margin-left: 260px; min-width: 0;">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    
    <!-- No external scripts. Everything is in app.js via Vite -->
    @stack('scripts')
</body>
</html>
