<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isotank Admin System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1F4FD8; /* Industrial Blue */
            --primary-hover: #163BA8;
            --secondary-color: #0F2A44; /* Dark Navy */
            --accent-color: #F97316; /* Warning Orange */
            --success-color: #16A34A;
            --danger-color: #DC2626;
            --sidebar-bg: #0F2A44;
            --body-bg: #F5F7FA; /* Neutral Industrial Gray */
            --card-bg: #FFFFFF;
            --card-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Very subtle, clean */
            --card-radius: 8px; /* Professional, not bubbly */
            --font-main: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--body-bg);
            color: #374151;
        }

        /* Sidebar Styling */
        .sidebar {
            background-color: var(--sidebar-bg); /* Solid Industrial Navy */
            color: white;
            box-shadow: 1px 0 0 rgba(255,255,255,0.05); /* Subtle separator */
            border: none;
            
            /* FIXED SIDEBAR IMPLEMENTATION */
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 260px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Container fixed, inner scrolls */
            z-index: 1000;
        }
        
        /* Inner scrollable area - hide scrollbar */
        .sidebar-content {
            flex: 1 1 auto; /* Grow and shrink */
            overflow-y: auto;
            min-height: 0; /* Crucial for scrolling */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE */
            padding-bottom: 2rem; /* Ensure last item is reachable */
        }
        .sidebar-content::-webkit-scrollbar { display: none; }

        .sidebar a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 8px 16px; /* Compact padding */
            display: flex;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 2px; /* Compact margin */
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.9rem; /* Slightly smaller font */
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
        }
        .sidebar a i { margin-right: 12px; font-size: 1rem; }
        .sidebar h4 { letter-spacing: 0.05em; margin-bottom: 1.5rem !important; margin-top: 1rem; }

        /* Card Styling - Industrial Clean */
        .card {
            border: 1px solid rgba(229, 231, 235, 0.8);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            background-color: white;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #F3F4F6;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            border-top-left-radius: var(--card-radius) !important;
            border-top-right-radius: var(--card-radius) !important;
        }
        .card-body { padding: 1.5rem; }

        /* Buttons -- Flat */
        .btn {
            border-radius: 8px;
            padding: 0.6rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: none !important;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover {
            background-color: var(--sidebar-bg); /* Use dark blue for better contrast */
            color: white;
        }
        
        /* Table Styling */
        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6B7280;
            background-color: #F9FAFB;
            border-bottom: 1px solid #E5E7EB;
            padding: 0.75rem 1rem;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #F3F4F6;
            font-size: 0.9rem;
        }
        
        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.8em;
            border-radius: 6px;
        }
        
        /* DataTables */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            padding: 6px 12px;
        }
        .dt-buttons .btn { margin-right: 5px; border-radius: 6px; }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            padding: 0.6rem 0.8rem;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(43, 76, 126, 0.2); /* Primary diluted */
        }

        /* Modals */
        .modal-content {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .modal-header {
            border-bottom: 1px solid #F3F4F6;
            padding: 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #F3F4F6;
            padding: 1.25rem 1.5rem;
        }
    </style>
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <nav class="sidebar p-3">
            <!-- 1. Header (Fixed) -->
            <div>
                <h4 class="mb-3 fw-bold text-white px-2 mt-2">ISOTANK</h4>
                
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
        <main class="flex-grow-1 p-4" style="margin-left: 260px; min-width: 0; background-color: #f4f6f9;">
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
