<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="theme" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Trading Dashboard' }} - DragonFortune AI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom Styles -->
    <style>
        :root {
            --df-primary: #3b82f6;
            --df-secondary: #8b5cf6;
            --df-success: #10b981;
            --df-warning: #f59e0b;
            --df-danger: #ef4444;
            --df-dark: #1e293b;
            --df-light: #f8fafc;
            --df-border: rgba(148, 163, 184, 0.2);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }

        /* Professional Card Styling */
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--df-border);
            border-radius: 16px;
            box-shadow: 
                0 4px 12px rgba(0, 0, 0, 0.05),
                0 2px 4px rgba(0, 0, 0, 0.02);
            backdrop-filter: blur(12px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .chart-card {
            background: rgba(30, 41, 59, 0.95);
            border-color: rgba(59, 130, 246, 0.2);
            box-shadow: 
                0 4px 12px rgba(0, 0, 0, 0.3),
                0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.1),
                0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .dark .chart-card:hover {
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.4),
                0 4px 8px rgba(0, 0, 0, 0.3);
        }

        /* Header Styling */
        .chart-header {
            padding: 1.5rem 1.5rem 0;
            border-bottom: 1px solid var(--df-border);
            margin-bottom: 1.5rem;
        }

        .dark .chart-header {
            border-bottom-color: rgba(59, 130, 246, 0.1);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .dark .chart-title {
            color: #e2e8f0;
        }

        .chart-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0.25rem 0 0;
        }

        .dark .chart-subtitle {
            color: #94a3b8;
        }

        /* Loading States */
        .chart-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            z-index: 10;
        }

        .dark .chart-loading {
            background: rgba(30, 41, 59, 0.9);
        }

        .loading-spinner {
            width: 2rem;
            height: 2rem;
            border: 3px solid var(--df-border);
            border-top: 3px solid var(--df-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Grid */
        .chart-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }

        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .chart-card {
                border-radius: 12px;
            }
            
            .chart-header {
                padding: 1rem 1rem 0;
                margin-bottom: 1rem;
            }
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .dark .status-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }

        .dark .status-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        .dark .status-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* Utility Classes */
        .text-gradient {
            background: linear-gradient(135deg, var(--df-primary), var(--df-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dark .glass-effect {
            background: rgba(30, 41, 59, 0.3);
            border-color: rgba(59, 130, 246, 0.2);
        }
    </style>

    @stack('styles')
</head>

<body class="antialiased">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-gradient" href="{{ route('dashboard') }}">
                <i class="bi bi-graph-up-arrow me-2"></i>
                DragonFortune AI
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button type="button" 
                        class="btn btn-outline-light btn-sm" 
                        @click="toggle()"
                        :title="dark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                    <i class="bi" :class="dark ? 'bi-sun' : 'bi-moon'"></i>
                </button>
                
                <!-- Last Update -->
                <small class="text-muted" x-show="$store.chartUtils" x-text="'Last update: ' + new Date().toLocaleTimeString()"></small>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-4">
        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Base Chart Controller -->
    <script src="{{ asset('js/base-chart-controller.js') }}"></script>
    
    <!-- Alpine Integration -->
    <script src="{{ asset('js/chart-alpine-integration.js') }}"></script>
    
    <!-- App JS -->
    @vite(['resources/js/app.js'])

    @stack('scripts')
</body>
</html>