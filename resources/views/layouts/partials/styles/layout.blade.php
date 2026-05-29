<style>
    /* ==========================================
       APP WRAPPER
       ========================================== */
    .app-wrapper {
        display: flex;
        min-height: 100vh;
        position: relative;
    }

    /* ==========================================
       MAIN CONTENT AREA
       ========================================== */
    .main-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        transition: margin var(--transition-slow);
    }

    .content-with-sidebar {
        margin-left: 0;
    }

    .content-full {
        margin-left: 0;
    }

    /* ==========================================
       PAGE CONTENT
       ========================================== */
    .page-content {
        flex: 1;
        padding-top: calc(var(--topbar-height) + 16px);
        padding-bottom: 80px;
        animation: fadeInUp 0.3s ease;
    }

    /* ==========================================
       DESKTOP TOPBAR
       ========================================== */
    .desktop-topbar {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1020;
        height: var(--topbar-height);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        padding: 0 16px;
        transition: all var(--transition);
    }

    .content-with-sidebar .desktop-topbar {
        left: 0;
    }

    .desktop-topbar.scrolled {
        box-shadow: var(--shadow-md);
    }

    /* ==========================================
       DESKTOP SIDEBAR
       ========================================== */
    .desktop-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        z-index: 1030;
        display: flex;
        flex-direction: column;
        transition: transform var(--transition-slow);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .desktop-sidebar .sidebar-brand {
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .desktop-sidebar .sidebar-brand i {
        font-size: 28px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .desktop-sidebar .sidebar-brand span {
        font-family: var(--font-heading);
        font-size: 1.25rem;
        font-weight: 800;
        color: white;
        letter-spacing: -0.5px;
    }

    .desktop-sidebar .sidebar-nav {
        flex: 1;
        padding: 12px;
    }

    .desktop-sidebar .nav-section {
        margin-bottom: 8px;
    }

    .desktop-sidebar .nav-section-title {
        padding: 8px 12px;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--gray-500);
    }

    .desktop-sidebar .nav-item {
        margin-bottom: 2px;
    }

    .desktop-sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: var(--radius);
        color: var(--gray-400);
        font-size: 0.8125rem;
        font-weight: 500;
        transition: all var(--transition-fast);
        text-decoration: none;
    }

    .desktop-sidebar .nav-link i {
        width: 20px;
        font-size: 1rem;
        text-align: center;
        flex-shrink: 0;
    }

    .desktop-sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: white;
    }

    .desktop-sidebar .nav-link.active {
        background: rgba(59, 130, 246, 0.2);
        color: white;
        font-weight: 600;
    }

    .desktop-sidebar .nav-link.active i {
        color: var(--primary-light);
    }

    .desktop-sidebar .nav-link .badge {
        margin-left: auto;
        font-size: 0.625rem;
        padding: 3px 8px;
    }

    .desktop-sidebar .sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    /* ==========================================
       MOBILE BOTTOM NAVIGATION (Android Style)
       ========================================== */
    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1025;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid var(--gray-200);
        padding: 4px 8px;
        padding-bottom: calc(4px + var(--safe-area-bottom));
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    }

    .mobile-bottom-nav .nav-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 4px;
    }

    .mobile-bottom-nav .nav-item {
        text-align: center;
    }

    .mobile-bottom-nav .nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px 4px;
        border-radius: var(--radius-md);
        color: var(--gray-500);
        text-decoration: none;
        transition: all var(--transition-fast);
        position: relative;
        min-height: 52px;
    }

    .mobile-bottom-nav .nav-link i {
        font-size: 1.35rem;
        margin-bottom: 2px;
        transition: all var(--transition-fast);
    }

    .mobile-bottom-nav .nav-link span {
        font-size: 0.6rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .mobile-bottom-nav .nav-link.active {
        color: var(--primary);
    }

    .mobile-bottom-nav .nav-link.active i {
        transform: scale(1.1);
    }

    .mobile-bottom-nav .nav-link .badge-dot {
        position: absolute;
        top: 4px;
        right: calc(50% - 10px);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--danger);
    }

    .mobile-bottom-nav .nav-link .badge-count {
        position: absolute;
        top: 2px;
        right: calc(50% - 14px);
        min-width: 16px;
        height: 16px;
        background: var(--danger);
        color: white;
        border-radius: 8px;
        font-size: 0.55rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        font-weight: 700;
    }

    /* ==========================================
       MOBILE DRAWER
       ========================================== */
    .drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: all var(--transition-slow);
    }

    .drawer-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .mobile-drawer {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 300px;
        max-width: 85vw;
        background: white;
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform var(--transition-slow);
        display: flex;
        flex-direction: column;
        box-shadow: var(--shadow-xl);
    }

    .mobile-drawer.show {
        transform: translateX(0);
    }

    .mobile-drawer .drawer-header {
        padding: 10px;
        /* background: var(--primary); */
        color: white;
        display: flex;
        align-items: center;
        /* gap: 12px; */
        border-bottom: 1px solid #3b82f6;
    }

    .drawer-logo {
        max-height: 100px;
        width: auto;
        object-fit: contain;
    }

    .brand-name {
        font-weight: 800;
        font-size: 1.8rem;
        white-space: nowrap;
        color: darkblue;
    }

    .mobile-drawer .drawer-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
    }

    .mobile-drawer .drawer-nav {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
    }

    .mobile-drawer .drawer-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        color: var(--gray-700);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.875rem;
        border-radius: var(--radius-md);
        transition: all var(--transition-fast);
    }

    .mobile-drawer .drawer-link i {
        font-size: 1.25rem;
        width: 24px;
        text-align: center;
        color: var(--gray-400);
    }

    .mobile-drawer .drawer-link:hover,
    .mobile-drawer .drawer-link.active {
        background: var(--primary-50);
        color: var(--primary);
    }

    .mobile-drawer .drawer-link:hover i,
    .mobile-drawer .drawer-link.active i {
        color: var(--primary);
    }

    .mobile-drawer .drawer-divider {
        height: 1px;
        background: var(--gray-200);
        margin: 8px 16px;
    }

    /* ==========================================
       FLOATING ACTION BUTTON
       ========================================== */
    .fab {
        display: none;
        position: fixed;
        bottom: calc(var(--bottom-nav-height) + 20px + var(--safe-area-bottom));
        right: 20px;
        z-index: 1020;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        border: none;
        box-shadow: 0 8px 24px rgba(26, 86, 219, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: all var(--transition);
    }

    .fab:active {
        transform: scale(0.9);
        box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
    }
</style>