<header class="desktop-topbar" id="desktopTopbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between w-100">

            {{-- Left: Mobile Menu Toggle + Brand --}}
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <button class="btn btn-light d-none d-md-flex d-lg-none align-items-center justify-content-center"
                    onclick="toggleDesktopSidebar()"
                    style="width: 40px; height: 40px; border-radius: var(--radius-md);">
                    <i class="fas fa-bars"></i>
                </button>

                <button class="btn btn-light d-md-none d-flex align-items-center justify-content-center"
                    onclick="openDrawer()" style="width: 40px; height: 40px; border-radius: var(--radius-md);">
                    <i class="fas fa-bars"></i>
                </button>

                <a href="{{ route('guest.home') }}" class="text-decoration-none d-flex align-items-center gap-2">
                    <i class="fas fa-shield-halved"
                        style="font-size: 1.5rem; background: linear-gradient(135deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                    <span class="fw-bold d-none d-sm-inline"
                        style="font-size: 1.125rem; font-family: var(--font-heading); color: #1f2937; letter-spacing: -0.5px;">
                        IRMSystem
                    </span>
                </a>
            </div>

            {{-- Right: Actions --}}
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @auth
                <a href="{{ route('incidents.create') }}"
                    class="btn btn-primary btn-sm d-none d-md-flex align-items-center gap-1"
                    style="border-radius: var(--radius-full); padding: 8px 16px;">
                    <i class="fas fa-plus"></i>
                    <span>Report</span>
                </a>

                {{-- Notifications --}}
                <div class="position-relative">
                    <button class="btn btn-light position-relative d-flex align-items-center justify-content-center"
                        onclick="toggleNotificationDropdown(event)" id="notificationBell"
                        style="width: 40px; height: 40px; border-radius: var(--radius-md);">
                        <i class="fas fa-bell"></i>
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                            id="notificationBadge" style="display: none; font-size: 0.625rem; padding: 3px 6px;">
                            0
                        </span>
                    </button>

                    {{-- Dropdown Menu - Fixed positioning for mobile --}}
                    <div class="notification-dropdown-menu" id="notificationDropdownMenu" style="display: none; position: fixed; top: var(--topbar-height, 60px); right: 8px; left: 8px;
                                max-width: 400px; max-height: 70vh; margin-left: auto;
                                background: white; border-radius: 12px;
                                box-shadow: 0 20px 60px rgba(0,0,0,0.2); z-index: 1050; overflow: hidden;">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold">Notifications</h6>
                            <a href="{{ route('notifications.index') }}"
                                class="text-decoration-none small fw-medium">View All</a>
                        </div>
                        <div id="notificationDropdownContent" style="max-height: calc(70vh - 60px); overflow-y: auto;">
                            <div class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                {{-- Theme Toggle --}}
                <button class="btn btn-light d-flex align-items-center justify-content-center" onclick="toggleTheme()"
                    style="width: 40px; height: 40px; border-radius: var(--radius-md);">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>

                {{-- User Menu --}}
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        style="border-radius: var(--radius-full); padding: 6px 12px 6px 6px;">
                        <img src="{{ Auth::user()?->avatar_url ?? asset('images/default-avatar.png') }}" alt="User"
                            class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                        <span class="d-none d-md-inline fw-medium" style="font-size: 0.8125rem;">
                            {{ Auth::user()?->name ?? 'Guest' }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        @auth
                        <div class="px-3 py-2">
                            <div class="fw-semibold">{{ Auth::user()->name }}</div>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('dashboard') }}" class="dropdown-item py-2">
                            <i class="fas fa-gauge-high me-2"></i> Dashboard
                        </a>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item py-2">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="{{ route('settings.index') }}" class="dropdown-item py-2">
                            <i class="fas fa-gear me-2"></i> Settings
                        </a>
                        <a href="{{ route('contact.form') }}" class="dropdown-item py-2">
                            <i class="fas fa-headset"></i> Contact
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger">
                                <i class="fas fa-right-from-bracket me-2"></i>Sign Out
                            </button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="dropdown-item py-2">
                            <i class="fas fa-right-to-bracket me-2"></i>Sign In
                        </a>

                        <a href="{{ route('contact.form') }}" class="dropdown-item py-2">
                            <i class="fas fa-right-to-bracket me-2"></i>Contact Us
                        </a>


                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdownMenu');
    const notificationContent = document.getElementById('notificationDropdownContent');
    const notificationBadge = document.getElementById('notificationBadge');

    // ==========================================
    // TOGGLE NOTIFICATION DROPDOWN
    // ==========================================
    window.toggleNotificationDropdown = function(event) {
        event.stopPropagation();

        if (!notificationDropdown) return;

        const isVisible = notificationDropdown.style.display === 'block';

        if (isVisible) {
            notificationDropdown.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        } else {
            notificationDropdown.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scroll on mobile
            loadNotificationDropdown();
        }
    };

    // ==========================================
    // CLOSE DROPDOWN WHEN CLICKING OUTSIDE
    // ==========================================
    document.addEventListener('click', function(event) {
        if (notificationDropdown && notificationDropdown.style.display === 'block') {
            if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
    });

    // ==========================================
    // CLOSE ON ESCAPE KEY
    // ==========================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && notificationDropdown && notificationDropdown.style.display === 'block') {
            notificationDropdown.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    // ==========================================
    // LOAD UNREAD COUNT
    // ==========================================
    function loadUnreadCount() {
        fetch('/notifications/unread-count', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(data => {
            if (notificationBadge) {
                if (data.count > 0) {
                    notificationBadge.textContent = data.count > 99 ? '99+' : data.count;
                    notificationBadge.style.display = 'inline-block';
                } else {
                    notificationBadge.style.display = 'none';
                }
            }
        })
        .catch(err => console.log('Count error:', err.message));
    }

    // ==========================================
    // LOAD NOTIFICATION DROPDOWN CONTENT
    // ==========================================
    function loadNotificationDropdown() {
        if (!notificationContent) return;

        notificationContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;

        fetch('/notifications/latest', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(notifications => {
            if (!notifications || notifications.length === 0) {
                notificationContent.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                        <small>No notifications yet</small>
                    </div>`;
                return;
            }

            notificationContent.innerHTML = notifications.map(n => `
                <a href="${n.url || '#'}" class="text-decoration-none" onclick="closeNotificationDropdown()">
                    <div class="px-3 py-2 border-bottom ${n.read ? '' : 'bg-light'}" style="cursor:pointer;">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;background:${n.color || '#6B7280'}15;color:${n.color || '#6B7280'};">
                                <i class="fas ${n.icon || 'fa-bell'}" style="font-size:0.75rem;"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="small fw-medium">${n.title || 'Notification'}</div>
                                <div class="text-muted" style="font-size:0.6875rem;">${n.message || ''}</div>
                                <small class="text-muted" style="font-size:0.625rem;">${n.time || ''}</small>
                            </div>
                            ${!n.read ? '<span style="width:8px;height:8px;border-radius:50%;background:#3B82F6;margin-top:6px;flex-shrink:0;"></span>' : ''}
                        </div>
                    </div>
                </a>
            `).join('');
        })
        .catch(err => {
            notificationContent.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <small>Failed to load notifications</small>
                </div>`;
        });
    }

    // ==========================================
    // CLOSE DROPDOWN HELPER
    // ==========================================
    window.closeNotificationDropdown = function() {
        if (notificationDropdown) {
            notificationDropdown.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    // ==========================================
    // INITIALIZE
    // ==========================================
    loadUnreadCount();
    setInterval(loadUnreadCount, 30000);

});
</script>
@endpush