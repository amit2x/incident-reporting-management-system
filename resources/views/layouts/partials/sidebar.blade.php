@auth
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="logo">
            <i class="fas fa-shield-alt"></i>
            <span>IRMSystem</span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            {{-- Dashboard --}}
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            {{-- Incidents --}}
            <li class="nav-item">
                <a href="{{ route('incidents.index') }}" class="nav-link {{ request()->routeIs('incidents.*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Incidents</span>
                    @if($openIncidentsCount ?? 0 > 0)
                        <span class="badge bg-danger ms-auto">{{ $openIncidentsCount }}</span>
                    @endif
                </a>
            </li>
            
            {{-- Create Incident --}}
            <li class="nav-item">
                <a href="{{ route('incidents.create') }}" class="nav-link {{ request()->routeIs('incidents.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle"></i>
                    <span>Report Incident</span>
                </a>
            </li>
            
            {{-- Reports --}}
            @can('view-reports')
            <li class="nav-item">
                <a href="#reportsSubmenu" class="nav-link" data-bs-toggle="collapse" role="button" 
                   aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a href="{{ route('reports.kpi') }}" class="nav-link {{ request()->routeIs('reports.kpi') ? 'active' : '' }}">
                                <i class="fas fa-chart-line"></i>
                                <span>KPI Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.department') }}" class="nav-link {{ request()->routeIs('reports.department') ? 'active' : '' }}">
                                <i class="fas fa-building"></i>
                                <span>Department Report</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.sla') }}" class="nav-link {{ request()->routeIs('reports.sla') ? 'active' : '' }}">
                                <i class="fas fa-clock"></i>
                                <span>SLA Report</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan
            
            {{-- Admin Menu --}}
            @role('admin|super-admin')
            <li class="nav-item">
                <a href="#adminSubmenu" class="nav-link" data-bs-toggle="collapse" role="button"
                   aria-expanded="{{ request()->routeIs('admin.*') ? 'true' : 'false' }}">
                    <i class="fas fa-cogs"></i>
                    <span>Administration</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.*') ? 'show' : '' }}" id="adminSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="fas fa-users"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                                <i class="fas fa-sitemap"></i>
                                <span>Departments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                <i class="fas fa-tags"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endrole
        </ul>
    </nav>
    
    {{-- Sidebar Footer --}}
    <div class="sidebar-footer mt-auto p-3">
        <div class="user-info d-flex align-items-center">
            <img src="{{ Auth::user()->avatar_url }}" alt="User" class="rounded-circle" width="32" height="32">
            <div class="ms-2">
                <small class="d-block text-white-50">{{ Auth::user()->name }}</small>
                <small class="text-muted">{{ Auth::user()->role_name }}</small>
            </div>
        </div>
    </div>
</aside>

@endauth