{{-- Quick Report FAB --}}
<a href="{{ route('incidents.create') }}"
   class="fab ripple"
   id="fabButton"
   aria-label="Report New Incident"
   data-bs-toggle="tooltip"
   data-bs-placement="left"
   title="Report New Incident">
    <i class="fas fa-plus"></i>
</a>

{{-- Optional: FAB Menu (Expandable) --}}
<div class="fab-menu" id="fabMenu" style="display: none;">
    <button class="fab-menu-item" onclick="window.location.href='{{ route('incidents.create') }}'" data-tooltip="Report Incident">
        <i class="fas fa-clipboard-list"></i>
    </button>
    <button class="fab-menu-item" onclick="window.location.href='{{ route('dashboard') }}'" data-tooltip="Dashboard">
        <i class="fas fa-gauge-high"></i>
    </button>
    <button class="fab-menu-item" onclick="window.location.href='{{ route('notifications.index') }}'" data-tooltip="Notifications">
        <i class="fas fa-bell"></i>
    </button>
</div>

<style>
    /* FAB Menu Styles */
    .fab-menu {
        position: fixed;
        bottom: calc(var(--bottom-nav-height) + 88px + var(--safe-area-bottom));
        right: 20px;
        z-index: 1019;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .fab-menu-item {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: white;
        color: var(--gray-700);
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        transition: all var(--transition-fast);
        animation: scaleIn 0.2s ease;
    }

    .fab-menu-item:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: scale(1.1);
    }

    /* Hide FAB on scroll */
    .fab-hidden {
        opacity: 0;
        transform: scale(0.5);
        pointer-events: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fab = document.getElementById('fabButton');
    if (!fab) return;

    let lastScroll = 0;
    let scrollTimeout;

    // Hide FAB on scroll down, show on scroll up
    window.addEventListener('scroll', function() {
        const currentScroll = window.scrollY;

        if (currentScroll > lastScroll && currentScroll > 200) {
            fab.classList.add('fab-hidden');
        } else {
            fab.classList.remove('fab-hidden');
        }

        lastScroll = currentScroll;

        // Show FAB when scroll stops
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            fab.classList.remove('fab-hidden');
        }, 1000);
    });

    // Long press FAB to show menu
    let pressTimer;

    fab.addEventListener('touchstart', function(e) {
        pressTimer = setTimeout(() => {
            const menu = document.getElementById('fabMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'flex' : 'none';
            }
        }, 500);
    });

    fab.addEventListener('touchend', function() {
        clearTimeout(pressTimer);
    });

    fab.addEventListener('touchmove', function() {
        clearTimeout(pressTimer);
    });

    // Close FAB menu on outside click
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('fabMenu');
        if (menu && !fab.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
});
</script>
