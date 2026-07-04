<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAuthenticated = document.querySelector('meta[name="auth-check"]')?.content === '1';   
    // alert(isAuthenticated);
    // ==========================================
    // DESKTOP SIDEBAR TOGGLE
    // ==========================================
    window.toggleDesktopSidebar = function() {
        document.getElementById('desktopSidebar').classList.toggle('show');
    };

    // ==========================================
    // MOBILE DRAWER
    // ==========================================
    window.openDrawer = function() {
        document.getElementById('mobileDrawer').classList.add('show');
        document.getElementById('drawerOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.closeDrawer = function() {
        document.getElementById('mobileDrawer').classList.remove('show');
        document.getElementById('drawerOverlay').classList.remove('show');
        document.body.style.overflow = '';
    };

    // Close drawer on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDrawer();
    });

    // ==========================================
    // THEME TOGGLE
    // ==========================================
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    window.toggleTheme = function() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon(next);
    };

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // ==========================================
    // BACK TO TOP
    // ==========================================
    const backToTopButton = document.getElementById('backToTop');
    
    if (backToTopButton) {
      window.addEventListener('scroll', function() {
        // Shows button at 300px and maintains flex layout properties
        backToTopButton.style.display = window.scrollY > 300 ? 'flex' : 'none';
      });
    }

    // ==========================================
    // TOPBAR SCROLL EFFECT
    // ==========================================
    let lastScroll = 0;
    const topbar = document.getElementById('desktopTopbar');

    window.addEventListener('scroll', function() {
        const currentScroll = window.scrollY;
        if (topbar) {
            if (currentScroll > 10) {
                topbar.classList.add('scrolled');
            } else {
                topbar.classList.remove('scrolled');
            }
        }
        lastScroll = currentScroll;
    });

    // ==========================================
    // NOTIFICATIONS LOADER
    // ==========================================

    function loadUnreadCount() {
        fetch('/notifications/unread-count', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Silent-Request': 'true'

            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Unread notifications:', data.count);

            // Update desktop notification badge
            const desktopBadge = document.querySelector('.notification-badge');
            if (desktopBadge) {
                if (data.count > 0) {
                    desktopBadge.textContent = data.count > 99 ? '99+' : data.count;
                    desktopBadge.style.display = 'inline-block';
                } else {
                    desktopBadge.style.display = 'none';
                }
            }

            // Update mobile bottom nav badge
            const mobileBadge = document.querySelector('.badge-count');
            if (mobileBadge) {
                if (data.count > 0) {
                    mobileBadge.textContent = data.count > 99 ? '99+' : data.count;
                    mobileBadge.style.display = 'flex';
                } else {
                    mobileBadge.style.display = 'none';
                }
            }

            // Update badge dot on mobile nav
            const badgeDot = document.querySelector('.badge-dot');
            if (badgeDot) {
                badgeDot.style.display = data.count > 0 ? 'block' : 'none';
            }
        })
        .catch(err => {
            console.log('Notification count error:', err.message);
        });
    }

    if (isAuthenticated) {
        loadUnreadCount(); 
        setInterval(loadUnreadCount, 30000); 
        
        if (document.querySelector('.notification-badge')) {
            loadUnreadCount();
            setInterval(loadUnreadCount, 30000);
        }
    }       

    // ==========================================
    // AUTO-HIDE ALERTS
    // ==========================================
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });

    // ==========================================
    // INITIALIZE TOOLTIPS & POPOVERS
    // ==========================================
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
    }

    //topbar
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
    // function loadUnreadCount() {
    //     fetch('/notifications/unread-count', {
    //         method: 'GET',
    //         headers: {
    //             'Accept': 'application/json',
    //             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    //             'X-Requested-With': 'XMLHttpRequest'
    //         },
    //         credentials: 'same-origin'
    //     })
    //     .then(response => {
    //         if (!response.ok) throw new Error('HTTP ' + response.status);
    //         return response.json();
    //     })
    //     .then(data => {
    //         if (notificationBadge) {
    //             if (data.count > 0) {
    //                 notificationBadge.textContent = data.count > 99 ? '99+' : data.count;
    //                 notificationBadge.style.display = 'inline-block';
    //             } else {
    //                 notificationBadge.style.display = 'none';
    //             }
    //         }
    //     })
    //     .catch(err => console.log('Count error:', err.message));
    // }

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
    // loadUnreadCount();
    // setInterval(loadUnreadCount, 30000);


});
</script>