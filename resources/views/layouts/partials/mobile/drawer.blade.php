{{-- Overlay --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

{{-- Drawer --}}
<div class="mobile-drawer" id="mobileDrawer">
    {{-- Header --}}
    @auth
    <div class="drawer-header">
        <img src="{{ asset('images/logo.png') }}" alt="IRMSystem Logo" class="drawer-logo">
        <span class="brand-name">IRMSystem</span>
    </div>

    @else
    <div class="drawer-header">

        <div class="drawer-header guest-header">
            <img src="{{ asset('images/logo.png') }}" alt="IRMSystem Logo" class="drawer-logo">
            <div>
                <span class="brand-name d-block">IRMSystem</span>
                <small class="text-muted">Welcome, Guest</small>
            </div>
        </div>
    </div>
    @endauth

    {{-- Navigation --}}
    <div class="drawer-nav">
        @auth
        <a href="{{ route('dashboard') }}" class="drawer-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="{{ route('incidents.index') }}"
            class="drawer-link {{ request()->routeIs('incidents.*') ? 'active' : '' }}">
            <i class="fas fa-clipboard-list"></i> All Incidents
        </a>
        <a href="{{ route('incidents.create') }}" class="drawer-link">
            <i class="fas fa-plus-circle"></i> Report Incident
        </a>

        @can('view-reports')

        <div class="drawer-divider"></div>

        <a href="{{ route('reports.index') }}"
            class="drawer-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i> Reports
        </a>
        @endcan
        <div class="drawer-divider"></div>

        <a href="{{ route('notifications.index') }}" class="drawer-link">
            <i class="fas fa-bell"></i> Notifications
            @php $unread = Auth::user()->unreadNotifications()->count(); @endphp
            @if($unread > 0)
            <span class="badge bg-danger ms-auto">{{ $unread }}</span>
            @endif
        </a>

        <a href="{{ route('profile.edit') }}" class="drawer-link">
            <i class="fas fa-user-gear"></i> Profile & Settings
        </a>

        @role('admin|super-admin')
        <div class="drawer-divider"></div>
        <a href="{{ route('admin.users.index') }}" class="drawer-link">
            <i class="fas fa-users-gear"></i> User Management
        </a>
        <a href="{{ route('admin.departments.index') }}" class="drawer-link">
            <i class="fas fa-diagram-project"></i> Departments
        </a>

        <a href="{{ route('admin.categories.index') }}" class="drawer-link">
            <i class="fas fa-diagram-project"></i> Categories
        </a>

        <a href="{{ route('admin.escalation-matrix.index') }}" class="drawer-link">
            <i class="fas fa-diagram-project"></i> Escalation Matrix
        </a>


        <a href="{{ route('admin.audit-logs') }}" class="drawer-link">
            <i class="fas fa-history"></i> Audit Logs
        </a>
        @endrole

        <div class="drawer-divider"></div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="drawer-link text-danger w-100 border-0 bg-transparent">
                <i class="fas fa-right-from-bracket"></i> Sign Out
            </button>
        </form>
        @else
        <a href="{{ route('guest.home') }}" class="drawer-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-house"></i> Home
        </a>
        <a href="{{ route('login') }}" class="drawer-link">
            <i class="fas fa-right-to-bracket"></i> Sign In
        </a>
        <a href="{{ route('contact.form') }}" class="drawer-link {{ request()->routeIs('contact.*') ? 'active' : '' }}">
            <i class="fas fa-headset"></i>
            <span>Contact</span>
        </a>
        @endauth
    </div>
</div>