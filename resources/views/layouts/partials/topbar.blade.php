<header class="topbar">
    <div class="container-fluid">
        <div class="row align-items-center">
            {{-- Mobile Menu Toggle --}}
            <div class="col-auto d-md-none">
                <button class="btn btn-light" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            {{-- Search Bar --}}
            <div class="col-md-4">
                <div class="search-bar">
                    <form action="{{ route('incidents.index') }}" method="GET">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search incidents..." value="{{ request('search') }}"
                                   aria-label="Search">
                        </div>
                    </form>
                </div>
            </div>
            
            {{-- Spacer --}}
            <div class="col-md-4"></div>
            
            {{-- Right Actions --}}
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    {{-- Quick Create --}}
                    <a href="{{ route('incidents.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i>
                        <span class="d-none d-md-inline ms-1">Report</span>
                    </a>
                    
                    {{-- Notifications --}}
                    <div class="dropdown">
                        <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px;">
                            <div class="px-3 py-2 border-bottom">
                                <h6 class="mb-0">Notifications</h6>
                            </div>
                            <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
                                {{-- Loaded dynamically via AJAX --}}
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="px-3 py-2 border-top text-center">
                                <a href="{{ route('notifications.index') }}" class="text-decoration-none small">
                                    View All Notifications
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Dark Mode Toggle --}}
                    <button class="btn btn-light" onclick="window.IRMS.toggleDarkMode()">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    {{-- User Menu --}}
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <img src="{{ Auth::user()?->avatar_url ?? asset('images/default-avatar.png') }}" alt="User" class="rounded-circle" width="32" height="32">
                            <span class="d-none d-md-inline">{{ Auth::user()?->name ?? 'Guest' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @auth
                                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                                <a href="{{ route('settings.index') }}" class="dropdown-item">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="dropdown-item">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </a>
                            @endauth
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
    // Load notifications
    function loadNotifications() {
        fetch('/api/v1/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            });
    }
    
    // Load on page load and every 30 seconds
    loadNotifications();
    setInterval(loadNotifications, 30000);
</script>
@endpush