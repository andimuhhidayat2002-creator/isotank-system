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
        .sidebar { background-color: #0d47a1; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 15px; display: block; border-radius: 5px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.2); color: white; }
        .card { border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .btn-primary { background-color: #0d47a1; border-color: #0d47a1; }
        /* Excel-like header colors */
        .dt-buttons .btn { margin-right: 5px; font-size: 0.8rem; }
        table.dataTable thead th { vertical-align: middle; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .dataTables_wrapper .dataTables_filter { margin-bottom: 1rem; }
        tfoot input { width: 100%; box-sizing: border-box; font-size: 0.8rem; padding: 3px; }
    </style>
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <nav class="sidebar flex-shrink-0 p-3" style="width: 260px; min-height: 100vh;">
            <h4 class="mb-4 fw-bold text-white px-2">ISOTANK</h4>
            
            <div class="mb-4 px-2 py-3 rounded bg-white bg-opacity-10 border border-white border-opacity-10">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <small class="text-white-50 d-block" style="font-size: 0.7rem;">Welcome back,</small>
                        <div class="fw-bold text-white lh-1">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <div class="badge bg-info text-dark bg-opacity-75 w-100">
                    {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
                </div>
            </div>

            <div class="d-flex flex-column">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.isotanks.index') }}" class="{{ request()->routeIs('admin.isotanks*') ? 'active' : '' }}">Master Isotanks</a>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.activities.index') }}" class="{{ request()->routeIs('admin.activities*') ? 'active' : '' }}">Activity Planner</a>
                    <a href="{{ route('admin.calibration-master.index') }}" class="{{ request()->routeIs('admin.calibration-master*') ? 'active' : '' }}">Calibration Master</a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Users</a>
                    <a href="{{ route('admin.inspection-items.index') }}" class="{{ request()->routeIs('admin.inspection-items*') ? 'active' : '' }}">Inspection Items</a>
                @endif
                <a href="{{ route('yard.index') }}" class="{{ request()->routeIs('yard.*') ? 'active' : '' }}">Yard Positioning</a>
                <hr>
                <small class="text-uppercase text-white-50 px-2 mb-2">Reports</small>
                <a href="{{ route('admin.reports.inspection') }}" class="{{ request()->routeIs('admin.reports.inspection*') ? 'active' : '' }}">Inspection Logs</a>
                <a href="{{ route('admin.reports.latest') }}" class="{{ request()->routeIs('admin.reports.latest') ? 'active' : '' }}">Latest Master Condition</a>
                <a href="{{ route('admin.reports.maintenance') }}" class="{{ request()->routeIs('admin.reports.maintenance*') ? 'active' : '' }}">Maintenance Jobs</a>
                <a href="{{ route('admin.reports.calibration') }}" class="{{ request()->routeIs('admin.reports.calibration') ? 'active' : '' }}">Calibration History</a>
                <a href="{{ route('admin.reports.vacuum') }}" class="{{ request()->routeIs('admin.reports.vacuum') ? 'active' : '' }}">Vacuum Suction</a>
                <hr>
                <form action="{{ route('logout') }}" method="POST" class="mt-auto">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">Logout</button>
                </form>
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
