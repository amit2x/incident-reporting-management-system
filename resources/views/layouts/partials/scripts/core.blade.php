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
        fetch('/api/v1/notifications/unread-count', {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer {{ session("api_token") ?? "" }}',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Not authenticated');
        })
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'block';
            } else if (badge) {
                badge.style.display = 'none';
            }
        })
        .catch(err => {
            console.log('Notifications not loaded:', err.message);
        });
    }

    loadUnreadCount();
    setInterval(loadUnreadCount, 30000);

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
