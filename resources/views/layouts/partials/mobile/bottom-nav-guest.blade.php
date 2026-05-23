<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="nav-grid">
        {{-- Home --}}
        <div class="nav-item">
            <a href="{{ url('/') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-house"></i>
                <span>Home</span>
            </a>
        </div>

        {{-- Features --}}
        <div class="nav-item">
            <a href="#" class="nav-link" onclick="openDrawer(); return false;">
                <i class="fas fa-shield-halved"></i>
                <span>Features</span>
            </a>
        </div>

        {{-- Login (Center - Prominent) --}}
        <div class="nav-item">
            <a href="{{ route('login') }}" class="nav-link nav-link-create">
                <div style="background: var(--primary); color: white; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: -16px; box-shadow: 0 4px 12px rgba(26,86,219,0.4);">
                    <i class="fas fa-right-to-bracket"></i>
                </div>
                <span style="margin-top: 2px;">Login</span>
            </a>
        </div>

        {{-- Register --}}
        <div class="nav-item">
            <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i>
                <span>Register</span>
            </a>
        </div>

        {{-- Help --}}
        <div class="nav-item">
            <a href="#" class="nav-link" onclick="openDrawer(); return false;">
                <i class="fas fa-circle-question"></i>
                <span>Help</span>
            </a>
        </div>
    </div>
</nav>
