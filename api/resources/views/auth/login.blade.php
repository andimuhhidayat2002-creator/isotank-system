<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Isotank System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2B4C7E;
            --primary-hover: #1E3A5F;
        }
        body { 
            background-color: #F8F9FA; 
            font-family: 'Inter', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            border: 1px solid rgba(229, 231, 235, 0.8);
            border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        }
        .btn-primary { 
            background-color: var(--primary-color); 
            border-color: var(--primary-color); 
            border-radius: 8px;
            font-weight: 500;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(43, 76, 126, 0.2);
        }
    </style>
</head>
<body>
    <div class="card login-card p-4 bg-white">
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="bi bi-box-seam display-4 text-primary"></i>
                <h3 class="fw-bold mt-2" style="color: var(--primary-color);">ISOTANK ADMIN</h3>
                <p class="text-muted small">Single Source of Truth</p>
            </div>
            
            @if($errors->any())
                <div class="alert alert-danger py-2 rounded-3 small">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@isotank.com">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-2">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
