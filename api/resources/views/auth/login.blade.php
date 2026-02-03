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
            --dark-bg: #12141a;
            --dark-card: #1c1f26;
            --dark-border: #2d323e;
            --dark-text-main: #e2e4e9;
            --dark-text-muted: #9499a6;
            --neon-blue: #3b82f6;
        }
        body { 
            background-color: var(--dark-bg); 
            color: var(--dark-text-main);
            font-family: 'Inter', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0;
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            background-color: var(--dark-card) !important;
            border: 1px solid var(--dark-border);
            border-radius: 16px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .btn-primary { 
            background-color: var(--neon-blue); 
            border-color: var(--neon-blue); 
            border-radius: 12px;
            font-weight: 600;
            padding: 12px;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        .form-control {
            background-color: #0f1115 !important;
            border: 1px solid var(--dark-border) !important;
            color: white !important;
            border-radius: 12px;
            padding: 12px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: var(--neon-blue) !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="card-body">
            <div class="text-center mb-5">
                <div class="mb-3 d-inline-block p-3 rounded-circle bg-primary bg-opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="var(--neon-blue)" class="bi bi-box-seam" viewBox="0 0 16 16">
                        <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.722V6.812L1 4.212V12.12l6.5 2.6zM12.758 1.512a1.5 1.5 0 0 1 .844 0l2.404.961a1.5 1.5 0 0 1 .908 1.459v8.434a1.5 1.5 0 0 1-.908 1.458l-6.5 2.6a1.5 1.5 0 0 1-1.112 0l-6.5-2.6A1.5 1.5 0 0 1 0 12.12V3.682a1.5 1.5 0 0 1 .908-1.458l2.404-.961z"/>
                    </svg>
                </div>
                <h3 class="fw-bold mt-2 text-white">ISOTANK ADMIN</h3>
                <p class="text-muted small text-uppercase" style="letter-spacing: 2px;">Command Center Login</p>
            </div>
            
            @if($errors->any())
                <div class="alert alert-danger py-2 rounded-3 small bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger">
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
                <button type="submit" class="btn btn-primary w-100 py-3">AUTHENTICATE</button>
            </form>
        </div>
    </div>
</body>
</html>
