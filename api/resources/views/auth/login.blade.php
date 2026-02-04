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
                <!-- Animated SVG Logo -->
                <div class="logo-container" style="margin-bottom: 20px;">
                    <svg width="280" height="140" viewBox="0 0 280 140" xmlns="http://www.w3.org/2000/svg" class="animated-logo">
                        <defs>
                            <!-- Gradient for Hexagon -->
                            <linearGradient id="hexGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#2d5016;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#1a3d0f;stop-opacity:1" />
                            </linearGradient>
                            
                            <!-- Gradient for Tank -->
                            <linearGradient id="tankGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#c0c0c0;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#e8e8e8;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#a8a8a8;stop-opacity:1" />
                            </linearGradient>
                            
                            <!-- Glow Filter -->
                            <filter id="glow">
                                <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>
                        
                        <!-- Hexagonal Frame -->
                        <g class="hex-frame" filter="url(#glow)">
                            <path d="M 35 30 L 55 20 L 75 30 L 75 60 L 55 70 L 35 60 Z" 
                                  fill="none" stroke="url(#hexGradient)" stroke-width="2.5" opacity="0.8"/>
                            <path d="M 30 32 L 50 22 L 70 32 L 70 58 L 50 68 L 30 58 Z" 
                                  fill="none" stroke="#3d6b1f" stroke-width="1.8" opacity="0.6"/>
                        </g>
                        
                        <!-- ISO Tank Illustration (Simplified) -->
                        <g class="tank-illustration">
                            <!-- Tank Body -->
                            <ellipse cx="55" cy="45" rx="18" ry="12" fill="url(#tankGradient)" opacity="0.9"/>
                            <rect x="37" y="39" width="36" height="12" fill="url(#tankGradient)" opacity="0.85"/>
                            <ellipse cx="55" cy="39" rx="18" ry="12" fill="#d0d0d0" opacity="0.95"/>
                            
                            <!-- Tank Rings -->
                            <line x1="40" y1="42" x2="40" y2="48" stroke="#888" stroke-width="0.8" opacity="0.6"/>
                            <line x1="48" y1="42" x2="48" y2="48" stroke="#888" stroke-width="0.8" opacity="0.6"/>
                            <line x1="62" y1="42" x2="62" y2="48" stroke="#888" stroke-width="0.8" opacity="0.6"/>
                            <line x1="70" y1="42" x2="70" y2="48" stroke="#888" stroke-width="0.8" opacity="0.6"/>
                        </g>
                        
                        <!-- Top Tank Silhouette -->
                        <g class="top-tank" opacity="0.7">
                            <rect x="220" y="8" width="50" height="8" fill="#1e3a8a" rx="1"/>
                            <rect x="225" y="12" width="40" height="4" fill="#2563eb" rx="0.5"/>
                            <circle cx="230" cy="14" r="1.5" fill="#3b82f6"/>
                            <circle cx="260" cy="14" r="1.5" fill="#3b82f6"/>
                        </g>
                        
                        <!-- Text: ISOTANK -->
                        <text x="90" y="50" font-family="Inter, Arial, sans-serif" font-size="32" font-weight="800" letter-spacing="1">
                            <tspan fill="#1e3a8a">ISO</tspan><tspan fill="#2d5016">TANK</tspan>
                        </text>
                        
                        <!-- Text: Management System -->
                        <text x="90" y="68" font-family="Inter, Arial, sans-serif" font-size="11" fill="#9ca3af" letter-spacing="0.5">
                            Management System
                        </text>
                        
                        <!-- Decorative Lines -->
                        <line x1="90" y1="72" x2="180" y2="72" stroke="#d97706" stroke-width="1.5" opacity="0.8"/>
                        <line x1="180" y1="72" x2="270" y2="72" stroke="#d97706" stroke-width="1" opacity="0.5"/>
                        
                        <!-- Text: PT. KAYAN LNG NUSANTARA -->
                        <text x="140" y="95" font-family="Inter, Arial, sans-serif" font-size="13" font-weight="700" fill="#1e3a8a" text-anchor="middle" letter-spacing="1.5">
                            PT. KAYAN LNG NUSANTARA
                        </text>
                    </svg>
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
