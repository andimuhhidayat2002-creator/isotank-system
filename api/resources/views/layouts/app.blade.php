<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAYAN LNG - Isotank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            /* KAYAN LNG Brand Colors */
            --kayan-navy: #2B4C7E;
            --kayan-navy-dark: #1E3A5F;
            --kayan-navy-light: #3D5F8F;
            --kayan-orange: #FF6B35;
            --kayan-orange-light: #FF8555;
            --kayan-orange-dark: #E55A2B;
            --kayan-blue: #60A5FA;
            --kayan-white: #FFFFFF;
            --kayan-gray-50: #F9FAFB;
            --kayan-gray-100: #F3F4F6;
            --kayan-gray-200: #E5E7EB;
            --kayan-gray-300: #D1D5DB;
            --kayan-gray-600: #4B5563;
            --kayan-gray-900: #111827;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--kayan-gray-50);
            color: var(--kayan-gray-900);
        }

        /* Sidebar Styling */
        .sidebar { 
            background: linear-gradient(180deg, var(--kayan-navy) 0%, var(--kayan-navy-dark) 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo img {
            width: 40px;
            height: 40px;
            filter: brightness(0) invert(1);
        }

        .sidebar-logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: white;
        }

        .sidebar a { 
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar a:hover { 
            background-color: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(4px);
        }

        .sidebar a.active { 
            background: linear-gradient(90deg, var(--kayan-orange) 0%, var(--kayan-orange-dark) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255,107,53,0.3);
        }

        .sidebar a i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            padding: 15px 15px 8px;
            margin-top: 10px;
        }

        .user-profile-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--kayan-orange), var(--kayan-orange-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 2px 8px rgba(255,107,53,0.3);
        }

        .user-role-badge {
            background: linear-gradient(90deg, var(--kayan-orange), var(--kayan-orange-light));
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-top: 8px;
        }

        /* Main Content */
        main {
            background-color: var(--kayan-gray-50);
        }

        /* Cards */
        .card { 
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-radius: 12px;
            background: white;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .card-header {
            background: linear-gradient(135deg, var(--kayan-navy), var(--kayan-navy-light));
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }

        /* Buttons */
        .btn-primary { 
            background: linear-gradient(90deg, var(--kayan-navy), var(--kayan-navy-light));
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, var(--kayan-navy-dark), var(--kayan-navy));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,76,126,0.3);
        }

        .btn-warning {
            background: linear-gradient(90deg, var(--kayan-orange), var(--kayan-orange-light));
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-warning:hover {
            background: linear-gradient(90deg, var(--kayan-orange-dark), var(--kayan-orange));
            color: white;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            border-left-color: #10B981;
        }

        .alert-danger {
            background-color: #FEE2E2;
            color: #991B1B;
            border-left-color: #EF4444;
        }

        .alert-warning {
            background-color: #FFF3E0;
            color: #92400E;
            border-left-color: var(--kayan-orange);
        }

        /* DataTables */
        .dt-buttons .btn { 
            margin-right: 5px;
            font-size: 0.85rem;
            border-radius: 6px;
            font-weight: 500;
        }

        table.dataTable thead th { 
            vertical-align: middle;
            border-bottom: 2px solid var(--kayan-navy);
            background: linear-gradient(135deg, var(--kayan-gray-50), var(--kayan-gray-100));
            color: var(--kayan-navy);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        table.dataTable tbody tr:hover {
            background-color: var(--kayan-gray-50);
        }

        .dataTables_wrapper .dataTables_filter { 
            margin-bottom: 1rem;
        }

        tfoot input { 
            width: 100%;
            box-sizing: border-box;
            font-size: 0.8rem;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid var(--kayan-gray-300);
        }

        /* Logout Button */
        .btn-logout {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(255,107,53,0.9);
            border-color: var(--kayan-orange);
            color: white;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 4px solid var(--kayan-orange);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--kayan-navy);
            margin: 0;
        }

        .page-subtitle {
            color: var(--kayan-gray-600);
            font-size: 0.95rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <nav class="sidebar flex-shrink-0 p-3" style="width: 280px; min-height: 100vh;">
            <!-- Logo -->
            <div class="sidebar-logo">
                <svg width="40" height="40" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="45" y="10" width="10" height="25" fill="white"/>
                    <rect x="45" y="65" width="10" height="25" fill="white"/>
                    <rect x="10" y="45" width="25" height="10" fill="white"/>
                    <rect x="65" y="45" width="25" height="10" fill="white"/>
                    <rect x="20" y="20" width="18" height="10" transform="rotate(45 29 25)" fill="white"/>
                    <rect x="62" y="20" width="18" height="10" transform="rotate(-45 71 25)" fill="white"/>
                    <rect x="20" y="70" width="18" height="10" transform="rotate(-45 29 75)" fill="white"/>
                    <rect x="62" y="70" width="18" height="10" transform="rotate(45 71 75)" fill="white"/>
                    <rect x="45" y="45" width="10" height="10" fill="white"/>
                </svg>
                <div class="sidebar-logo-text">KAYAN LNG</div>
            </div>
            
            <!-- User Profile -->
            <div class="user-profile-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="user-avatar me-3">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-white-50 d-block" style="font-size: 0.7rem;">Welcome back,</small>
                        <div class="fw-bold text-white lh-1" style="font-size: 0.95rem;">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <div class="user-role-badge">
                    {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
                </div>
            </div>

            <!-- Navigation -->
            <div class="d-flex flex-column">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.isotanks.index') }}" class="{{ request()->routeIs('admin.isotanks*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Master Isotanks</span>
                </a>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.activities.index') }}" class="{{ request()->routeIs('admin.activities*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>
                        <span>Activity Planner</span>
                    </a>
                    <a href="{{ route('admin.calibration-master.index') }}" class="{{ request()->routeIs('admin.calibration-master*') ? 'active' : '' }}">
                        <i class="bi bi-tools"></i>
                        <span>Calibration Master</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                    <a href="{{ route('admin.inspection-items.index') }}" class="{{ request()->routeIs('admin.inspection-items*') ? 'active' : '' }}">
                        <i class="bi bi-list-check"></i>
                        <span>Inspection Items</span>
                    </a>
                @endif
                <a href="{{ route('yard.index') }}" class="{{ request()->routeIs('yard.*') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3"></i>
                    <span>Yard Positioning</span>
                </a>
                
                <!-- Reports Section -->
                <div class="sidebar-section-title">Reports</div>
                <a href="{{ route('admin.reports.inspection') }}" class="{{ request()->routeIs('admin.reports.inspection*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Inspection Logs</span>
                </a>
                <a href="{{ route('admin.reports.latest') }}" class="{{ request()->routeIs('admin.reports.latest') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Latest Master Condition</span>
                </a>
                <a href="{{ route('admin.reports.maintenance') }}" class="{{ request()->routeIs('admin.reports.maintenance*') ? 'active' : '' }}">
                    <i class="bi bi-wrench"></i>
                    <span>Maintenance Jobs</span>
                </a>
                <a href="{{ route('admin.reports.calibration') }}" class="{{ request()->routeIs('admin.reports.calibration') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i>
                    <span>Calibration History</span>
                </a>
                <a href="{{ route('admin.reports.vacuum') }}" class="{{ request()->routeIs('admin.reports.vacuum') ? 'active' : '' }}">
                    <i class="bi bi-speedometer"></i>
                    <span>Vacuum Suction</span>
                </a>
                
                <!-- Logout -->
                <div style="margin-top: auto; padding-top: 20px;">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-logout w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow-1 p-4" style="min-width: 0; background-color: #f4f6f9;">
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
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    @stack('scripts')
</body>
</html>
