<script>
document.addEventListener('DOMContentLoaded', function() {

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
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            backToTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
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
                'X-Requested-With': 'XMLHttpRequest'
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

    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadUnreadCount();
        // Refresh every 30 seconds
        setInterval(loadUnreadCount, 30000);
    });


    if (document.querySelector('.notification-badge')) {
        loadUnreadCount();
        setInterval(loadUnreadCount, 30000);
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

});
</script>
