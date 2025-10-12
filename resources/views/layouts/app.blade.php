<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Solar Battery EV Charger')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        
        .sidebar .nav-link {
            color: #495057;
            border-radius: 0;
            padding: 0.75rem 1rem;
        }
        
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }
        
        .sidebar .nav-link.active {
            background-color: #dee2e6;
            font-weight: bold;
        }
        
        .content-area {
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                height: auto;
                position: fixed;
                top: 0;
                width: 100%;
                z-index: 1000;
            }
            
            main {
                margin-top: 60px;
            }
            
            .navbar-toggler {
                padding: 0.25rem 0.5rem;
                font-size: 1rem;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Mobile Navbar -->
            <div class="d-md-none bg-light py-2 px-3 w-100 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Solar Battery EV Charger</h4>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4 d-none d-md-block">
                        <h4>Solar Battery EV Charger</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                                <i class="bi bi-house-door"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('zappi/status') ? 'active' : '' }}" href="{{ url('/zappi/status') }}">
                                <i class="bi bi-lightning-charge"></i> Zappi Status
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('sunsync/dashboard') ? 'active' : '' }}" href="{{ url('/sunsync/dashboard') }}">
                                <i class="bi bi-sun"></i> SunSync Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('ev-charging/status') ? 'active' : '' }}" href="{{ url('/ev-charging/status') }}">
                                <i class="bi bi-ev-station"></i> Control Inverter by EV Status
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                                <i class="bi bi-gear-fill"></i> Settings
                            </a>
                        </li>
                        <!-- Add more menu items as needed -->
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html> 