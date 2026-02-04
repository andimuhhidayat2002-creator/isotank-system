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
                <!-- Animated SVG Logo - Redesigned to Match Original -->
                <div class="logo-container" style="margin-bottom: 20px;">
                    <svg width="100%" height="auto" viewBox="0 0 820 260" xmlns="http://www.w3.org/2000/svg" class="animated-logo" style="max-width: 650px;">
                        <defs>
                            <!-- Gradients -->
                            <linearGradient id="hexGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#2d5a1e;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#1a3d12;stop-opacity:1" />
                            </linearGradient>
                            
                            <linearGradient id="tankGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#b8b8b8;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#e0e0e0;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#a0a0a0;stop-opacity:1" />
                            </linearGradient>
                            
                            <radialGradient id="tankShine">
                                <stop offset="0%" style="stop-color:#ffffff;stop-opacity:0.6" />
                                <stop offset="100%" style="stop-color:#c0c0c0;stop-opacity:0.2" />
                            </radialGradient>
                            
                            <!-- Glow Filter -->
                            <filter id="glow">
                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>
                        
                        <!-- HEXAGONAL FRAME (Left Side) -->
                        <g class="hex-frame" transform="translate(20, 30)" filter="url(#glow)">
                            <!-- Outer Hexagon -->
                            <path d="M 100 20 L 180 60 L 180 140 L 100 180 L 20 140 L 20 60 Z" 
                                  fill="none" stroke="#2d5a1e" stroke-width="8" opacity="0.9"/>
                            
                            <!-- Middle Hexagon -->
                            <path d="M 100 35 L 165 67 L 165 133 L 100 165 L 35 133 L 35 67 Z" 
                                  fill="none" stroke="#3d6b1f" stroke-width="6" opacity="0.8"/>
                            
                            <!-- Inner Hexagon -->
                            <path d="M 100 45 L 155 72 L 155 128 L 100 155 L 45 128 L 45 72 Z" 
                                  fill="none" stroke="#4d7b2f" stroke-width="4" opacity="0.7"/>
                            
                            <!-- Decorative Inner Lines -->
                            <line x1="45" y1="72" x2="100" y2="45" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                            <line x1="100" y1="45" x2="155" y2="72" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                            <line x1="155" y1="72" x2="155" y2="128" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                            <line x1="155" y1="128" x2="100" y2="155" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                            <line x1="100" y1="155" x2="45" y2="128" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                            <line x1="45" y1="128" x2="45" y2="72" stroke="#2d5a1e" stroke-width="2" opacity="0.5"/>
                        </g>
                        
                        <!-- 3D ISO TANK ILLUSTRATION (Inside Hexagon) -->
                        <g class="tank-illustration" transform="translate(60, 70)">
                            <!-- Tank Back Ellipse -->
                            <ellipse cx="60" cy="80" rx="45" ry="28" fill="url(#tankGradient)" opacity="0.85"/>
                            
                            <!-- Tank Cylinder Body -->
                            <rect x="15" y="52" width="90" height="56" fill="url(#tankGradient)"/>
                            
                            <!-- Tank Front Ellipse -->
                            <ellipse cx="60" cy="52" rx="45" ry="28" fill="#d8d8d8"/>
                            <ellipse cx="60" cy="52" rx="45" ry="28" fill="url(#tankShine)" opacity="0.4"/>
                            
                            <!-- Tank Rings/Bands -->
                            <ellipse cx="60" cy="60" rx="45" ry="28" fill="none" stroke="#888" stroke-width="1.5" opacity="0.6"/>
                            <ellipse cx="60" cy="70" rx="45" ry="28" fill="none" stroke="#888" stroke-width="1.5" opacity="0.6"/>
                            <ellipse cx="60" cy="80" rx="45" ry="28" fill="none" stroke="#888" stroke-width="1.5" opacity="0.6"/>
                            <ellipse cx="60" cy="90" rx="45" ry="28" fill="none" stroke="#888" stroke-width="1.5" opacity="0.6"/>
                            
                            <!-- Vertical Support Lines -->
                            <line x1="20" y1="55" x2="20" y2="105" stroke="#707070" stroke-width="2" opacity="0.5"/>
                            <line x1="100" y1="55" x2="100" y2="105" stroke="#707070" stroke-width="2" opacity="0.5"/>
                            
                            <!-- Highlight -->
                            <ellipse cx="60" cy="52" rx="30" ry="18" fill="white" opacity="0.25"/>
                        </g>
                        
                        <!-- TOP TANK SILHOUETTE (Right Side) -->
                        <g class="top-tank" transform="translate(620, 20)" opacity="0.8">
                            <!-- Tank Body -->
                            <rect x="0" y="15" width="180" height="25" fill="#1e3a8a" rx="3"/>
                            <rect x="10" y="18" width="160" height="8" fill="#2563eb" rx="2"/>
                            
                            <!-- Tank Details -->
                            <circle cx="25" cy="22" r="3" fill="#3b82f6"/>
                            <circle cx="155" cy="22" r="3" fill="#3b82f6"/>
                            <rect x="80" y="10" width="20" height="8" fill="#1e3a8a" rx="2"/>
                            
                            <!-- Crane/Lifting Structure -->
                            <line x1="140" y1="0" x2="140" y2="15" stroke="#1e3a8a" stroke-width="3"/>
                            <line x1="130" y1="5" x2="150" y2="5" stroke="#1e3a8a" stroke-width="2"/>
                            <line x1="135" y1="0" x2="145" y2="0" stroke="#2563eb" stroke-width="2"/>
                        </g>
                        
                        <!-- TEXT: ISOTANK -->
                        <text x="360" y="110" font-family="'Inter', Arial, sans-serif" font-size="90" font-weight="900" letter-spacing="2">
                            <tspan fill="#1e3a8a">ISO</tspan><tspan fill="#2d5a1e">TANK</tspan>
                        </text>
                        
                        <!-- TEXT: Management System -->
                        <text x="360" y="145" font-family="'Inter', Arial, sans-serif" font-size="28" font-weight="400" fill="#4a5568" letter-spacing="1">
                            Management System
                        </text>
                        
                        <!-- DECORATIVE LINES -->
                        <line x1="300" y1="165" x2="520" y2="165" stroke="#d97706" stroke-width="3" opacity="0.9"/>
                        <line x1="520" y1="165" x2="820" y2="165" stroke="#d97706" stroke-width="2" opacity="0.6"/>
                        
                        <!-- TEXT: PT. KAYAN LNG NUSANTARA -->
                        <text x="410" y="210" font-family="'Inter', Arial, sans-serif" font-size="36" font-weight="700" fill="#1e3a8a" text-anchor="middle" letter-spacing="3">
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
