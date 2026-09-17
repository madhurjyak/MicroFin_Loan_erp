<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SFB MFI ERP') — IndiaLend Pro</title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Premium Vanilla CSS Design System -->
    <link rel="stylesheet" href="{{ asset('css/premium-dashboard.css') }}">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @stack('head')
</head>

<body>

    <div class="app-container">

        <!-- ── Sidebar ─────────────────────────────────────────────────────── -->
        <aside class="app-sidebar" id="appSidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <div class="brand-logo">
                    <div class="brand-icon">₹</div>
                    <div class="brand-text">
                        <h2>IndiaLend Pro</h2>
                        <p>SFB / NBFC-MFI ERP</p>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="sidebar-nav">
                {{-- ── Overview ── --}}
                <p class="nav-section">Overview</p>
                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                {{-- ── Create centers and groups (Agent & Admin) ── --}}
                @if(auth()->user()->hasRole('agent', 'admin'))
                <p class="nav-section">Centers And Groups</p>
                <a href="{{ route('los.center') }}"
                    class="nav-item {{ request()->routeIs('los.center') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                     </svg>
                    Centers
                </a>
                <a href="{{ route('los.group') }}"
                    class="nav-item {{ request()->routeIs('los.group') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Groups
                </a>
                <a href="{{ route('los.member') }}"
                    class="nav-item {{ request()->routeIs('los.member') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Members
                </a>

                <p class="nav-section">Loan Origination</p>
                <a href="{{ route('los.apply') }}"
                    class="nav-item {{ request()->routeIs('los.apply') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Apply Loan
                </a>
                <a href="{{ route('los.my-applications') }}"
                    class="nav-item {{ request()->routeIs('los.my-applications') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    My Applications
                </a>
                @endif

                {{-- ── Loan Origination Pipeline (Manager & Admin) ── --}}
                @if(auth()->user()->hasRole('manager', 'admin'))
                <p class="nav-section">Underwriting</p>
                <a href="{{ route('los.pipeline') }}"
                    class="nav-item {{ request()->routeIs('los.pipeline', 'los.review') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    LOS Pipeline
                </a>
                @endif

                {{-- ── Loan Management ── --}}
                <p class="nav-section">Loan Management</p>
                <a href="{{ route('lms.cds') }}"
                    class="nav-item {{ request()->routeIs('lms.cds') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Kendra Collection Sheet
                </a>

                {{-- ── Recovery & Legal ── --}}
                <p class="nav-section">Recovery & Legal</p>
                <a href="{{ route('recovery.console') }}"
                    class="nav-item {{ request()->routeIs('recovery.console') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Delinquency Console
                </a>
                @if(auth()->user()->hasRole('manager', 'admin'))
                <a href="{{ route('recovery.legal') }}"
                    class="nav-item {{ request()->routeIs('recovery.legal*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                    </svg>
                    Legal & OTS Hub
                </a>
                @endif

                {{-- ── Admin ── --}}
                @if(auth()->user()->isAdmin())
                <p class="nav-section">Administration</p>
                <a href="{{ route('admin.users') }}"
                    class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    User Management
                </a>
                <a href="{{ route('admin.config') }}"
                    class="nav-item {{ request()->routeIs('admin.config') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    System Config
                </a>
                @endif
            </nav>

            <!-- Footer -->
            <div class="sidebar-footer">
                <p>RBI Compliant · v2.0</p>
            </div>
        </aside>

        <!-- ── Main Content ────────────────────────────────────────────────── -->
        <div class="app-main">

            <!-- Topbar -->
            <header class="app-header">
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <div class="header-actions">
                    <span class="date-display">{{ now()->format('d M Y, D') }}</span>

                    {{-- Role Badge --}}
                    <span class="role-badge {{ auth()->user()->role }}">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>

                    {{-- User Dropdown --}}
                    <div class="user-menu-wrapper">
                        <button id="userMenuBtn" class="user-menu-btn" aria-expanded="false">
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="userDropdown" class="user-dropdown">
                            <div class="dropdown-header">
                                <p class="dropdown-name">{{ auth()->user()->name }}</p>
                                <p class="dropdown-email">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
            <div class="flash-message flash-success">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="flash-message flash-error">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
            @endif

            <!-- Page Content -->
            <main class="app-content">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Vanilla JS for Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dropdown Toggle Logic
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isExpanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
                    userMenuBtn.setAttribute('aria-expanded', !isExpanded);
                    userDropdown.classList.toggle('show');
                });

                document.addEventListener('click', (e) => {
                    if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userMenuBtn.setAttribute('aria-expanded', 'false');
                        userDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>

</html>