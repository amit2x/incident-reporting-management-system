<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="nav-grid">
        {{-- Home --}}
        <div class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-house"></i>
                <span>Home</span>
            </a>
        </div>

        {{-- Incidents --}}
        <div class="nav-item">
            <a href="{{ route('incidents.index') }}"
                class="nav-link {{ request()->routeIs('incidents.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i>
                <span>Incidents</span>
                @php $openCount = \App\Models\Incident::whereIn('status',
                ['open','acknowledged','in_progress'])->count(); @endphp
                @if($openCount > 0)
                <span class="badge-count">{{ $openCount > 99 ? '99+' : $openCount }}</span>
                @endif
            </a>
        </div>

        {{-- Create (Center - Prominent) --}}
        <div class="nav-item">
            <a href="{{ route('incidents.create') }}" class="nav-link nav-link-create">
                <div
                    style="background: var(--primary); color: white; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: -16px; box-shadow: 0 4px 12px rgba(26,86,219,0.4);">
                    <i class="fas fa-plus"></i>
                </div>
                <span style="margin-top: 2px;">Report</span>
            </a>
        </div>

        {{-- Reports --}}
        <div class="nav-item">
            <a href="{{ route('reports.index') }}"
                class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="fas fa-chart-simple"></i>
                <span>Reports</span>
            </a>
        </div>

        {{-- More / Menu --}}
        <div class="nav-item">
            <a href="#" class="nav-link" onclick="openDrawer(); return false;">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
                @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                @if($unreadCount > 0)
                <span class="badge-dot"></span>
                @endif
            </a>
        </div>
    </div>
</nav>