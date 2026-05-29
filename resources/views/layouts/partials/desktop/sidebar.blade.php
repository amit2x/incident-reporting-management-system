<aside class="desktop-sidebar" id="desktopSidebar">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <i class="fas fa-shield-halved"></i>
        <span>IRMSystem</span>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">

        {{-- Main Section --}}
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-item">
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('incidents.index') }}"
                    class="nav-link {{ request()->routeIs('incidents.*') && !request()->routeIs('incidents.create') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i>
                    <span>All Incidents</span>
                    @php $openCount = \App\Models\Incident::whereIn('status',
                    ['open','acknowledged','in_progress'])->count(); @endphp
                    @if($openCount > 0)
                    <span class="badge bg-danger ms-auto">{{ $openCount }}</span>
                    @endif
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('incidents.create') }}"
                    class="nav-link {{ request()->routeIs('incidents.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i>
                    <span>Report Incident</span>
                </a>
            </div>
        </div>

        {{-- Reports Section --}}
        @can('view-reports')
        <div class="nav-section">
            <div class="nav-section-title">Reports</div>
            <div class="nav-item">
                <a href="{{ route('reports.index') }}"
                    class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice"></i>
                    <span>All Reports</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="{{ route('reports.kpi') }}"
                    class="nav-link {{ request()->routeIs('reports.kpi') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>KPI Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('reports.department') }}"
                    class="nav-link {{ request()->routeIs('reports.department') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Department Report</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('reports.sla') }}"
                    class="nav-link {{ request()->routeIs('reports.sla') ? 'active' : '' }}">
                    <i class="fas fa-stopwatch"></i>
                    <span>SLA Report</span>
                </a>
            </div>
        </div>
        @endcan

        {{-- Admin Section --}}
        @role('admin|super-admin')
        <div class="nav-section">
            <div class="nav-section-title">Administration</div>
            <div class="nav-item">
                <a href="{{ route('admin.users.index') }}"
                    class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users-gear"></i>
                    <span>User Management</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.departments.index') }}"
                    class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                    <i class="fas fa-diagram-project"></i>
                    <span>Departments</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.categories.index') }}"
                    class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.escalation-matrix.index') }}"
                    class="nav-link {{ request()->routeIs('admin.escalation-matrix.*') ? 'active' : '' }}">
                    <i class="fas fa-arrow-up-right-dots"></i>
                    <span>Escalation Matrix</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.audit-logs') }}"
                    class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>Audit Logs</span>
                </a>
            </div>
        </div>
        @endrole
    </nav>

    {{-- User Info --}}
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ Auth::user()->avatar_url }}" alt="User" class="rounded-circle" width="40" height="40"
                style="object-fit: cover;">
            <div class="min-width-0">
                <div class="text-white small fw-semibold text-truncate">{{ Auth::user()->name }}</div>
                <div class="text-muted" style="font-size: 0.6875rem;">{{ Auth::user()->role_name }}</div>
            </div>
        </div>
    </div>
</aside>