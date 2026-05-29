{{-- Update in: resources/views/layouts/partials/mobile/bottom-nav-guest.blade.php --}}
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="nav-grid">
        {{-- Home --}}
        <div class="nav-item">
            <a href="{{ route('guest.home') }}" class="nav-link {{ request()->routeIs('guest.home') ? 'active' : '' }}">
                <i class="fas fa-house"></i>
                <span>Home</span>
            </a>
        </div>

        {{-- Features --}}
        <div class="nav-item">
            <a href="{{ route('guest.features') }}"
                class="nav-link {{ request()->routeIs('guest.features') ? 'active' : '' }}">
                <i class="fas fa-star"></i>
                <span>Features</span>
            </a>
        </div>

        {{-- Login (Center - Prominent) --}}
        <div class="nav-item">
            <a href="{{ route('login') }}" class="nav-link nav-link-create">
                <div
                    style="background: var(--primary); color: white; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: -16px; box-shadow: 0 4px 12px rgba(26,86,219,0.4);">
                    <i class="fas fa-right-to-bracket"></i>
                </div>
                <span style="margin-top: 2px;">Login</span>
            </a>
        </div>

        {{-- Help --}}
        <div class="nav-item">
            <a href="{{ route('guest.help') }}" class="nav-link {{ request()->routeIs('guest.help') ? 'active' : '' }}">
                <i class="fas fa-circle-question"></i>
                <span>Help</span>
            </a>
        </div>

        <a href="{{ route('contact.form') }}" class="nav-link {{ request()->routeIs('contact.*') ? 'active' : '' }}">
            <i class="fas fa-headset"></i>
            <span>Contact</span>
        </a>
    </div>
</nav>