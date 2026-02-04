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
        
        /* Logo Animations */
        .animated-logo {
            animation: logoFadeIn 1.2s ease-out;
        }
        
        @keyframes logoFadeIn {
            0% {
                opacity: 0;
                transform: scale(0.9) translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .hex-frame {
            animation: hexPulse 3s ease-in-out infinite;
            transform-origin: 55px 45px;
        }
        
        @keyframes hexPulse {
            0%, 100% {
                opacity: 0.8;
                filter: drop-shadow(0 0 3px rgba(61, 107, 31, 0.4));
            }
            50% {
                opacity: 1;
                filter: drop-shadow(0 0 8px rgba(61, 107, 31, 0.7));
            }
        }
        
        .tank-illustration {
            animation: tankGlow 4s ease-in-out infinite;
        }
        
        @keyframes tankGlow {
            0%, 100% {
                filter: drop-shadow(0 0 2px rgba(200, 200, 200, 0.3));
            }
            50% {
                filter: drop-shadow(0 0 5px rgba(200, 200, 200, 0.6));
            }
        }
        
        .top-tank {
            animation: tankFloat 2.5s ease-in-out infinite;
        }
        
        @keyframes tankFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-3px);
            }
        }
        
        .logo-container {
            animation: containerFade 1s ease-out;
        }
        
        @keyframes containerFade {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="card-body">
            <div class="text-center mb-4">
                <!-- Company Logo -->
                <!-- Company Logo -->
                <div class="logo-container" style="margin-bottom: 20px;">
                    <div style="background-color: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                        <img src="{{ asset('images/isotank_logo_premium.jpg') }}" 
                             alt="Isotank Management System" 
                             class="animated-logo"
                             style="max-width: 100%; height: auto;">
                    </div>
                </div>
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
